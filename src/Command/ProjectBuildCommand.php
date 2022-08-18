<?php

namespace App\Command;

use App\UnityShellCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
  name: 'project:build',
  description: 'Builds Lando and Platform hosting environments for this project',
  hidden: FALSE,
  aliases: ['pb']
)]
/**
 * Command to build a Unity2 project.
 */
class ProjectBuildCommand extends UnityShellCommand {

  /**
   * The command name.
   *
   * @var string
   */
  protected static $defaultName = 'project:build';

  /**
   * The command execution.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   CLI input interface.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   CLI output interface.
   *
   * @return int
   *   return 0 if command successful, non-zero for failure.
   *
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $solr_required = FALSE;

    // @todo Spin most of this code out into separate functions or services
    // and remove all these todo's.
    // - Have each hosting option (PSH, Lando) as a service implementing a host
    //   interface and responsible for generating configuration for that host.
    //   Register the required services into a repository for processing by
    //   the build command.
    // - Display notice when a site is removed and there are files in the
    //   project/site_id folder
    // - Remove all config if an entry is removed from project.yml.
    // - Improve error handling.
    // Check we are running in the root of a Unity repo and have a project file.
    if (!$this->fs()->exists('/project/project.yml')) {
      $io->error('Please ensure you are in a Unity project and that project/project.yml exists before running this command.');
      return Command::FAILURE;
    }

    $io->title('Building host environment');

    // Unity2 Project file.
    $project = $this->fs()->readFile('/project/project.yml');

    // Platform SH specific configuration.
    $platform = $this->fs()->readFile('/.hosting/platformsh/.platform.app.template.yaml');
    $services = $this->fs()->readFile('/.hosting/platformsh/.services.template.yaml');

    // Create the Platform and Lando application name.
    $platform['name'] = $this->createApplicationId($project['project_name']);
    $lando['name'] = $platform['name'];

    if (empty($project['sites'])) {
      $io->warning('This project does not have any sites defined, please add some using site:add before running this command.');
      return Command::INVALID;
    }

    // @todo Check if config exists, cleanup.
    foreach ($project['sites'] as $site_id => $site) {

      $io->section('Creating Lando and Platform SH configuration for: ' . $site_id);

      // Create Lando proxy.
      $io->text('Lando: proxy entry');
      $lando['proxy']['appserver'][] = $site['url'] . '.lndo.site';

      $io->text('Platform: site status, ' . $site['status']);

      // Create database relationship.
      if (!empty($site['database'])) {
        $io->text('Platform: database relationship');
        $platform['relationships'][$site_id] = 'db:' . $site['database'];
      }

      // Create solr relationship.
      if (!empty($site['solr'])) {
        $io->text('Platform: solr relationship');
        $platform['relationships'][$site_id . '_solr'] = 'solr:' . $site['solr'];

        $lando['services'][$site_id . '_solr'] = [
          'type' => 'solr:7',
          'portforward' => TRUE,
          'core' => 'default',
          'config' => [
            'dir' => '.lando/config/solr/7.x/default',
          ],
        ];
      }

      // Create Platform SH services.
      $io->text('Platform: database config');
      $services['db']['configuration']['schemas'][] = $site_id . 'db';
      $services['db']['configuration']['endpoints'][$site_id] = [
        'default_schema' => $site_id . 'db',
        'privileges' => [
          $site_id . 'db' => 'admin',
        ],
      ];

      if (!empty($site['solr'])) {
        $io->text('Platform: solr config');
        $solr_conf_dir = new TaggedValue('archive', 'solr_config/');
        $services['solr']['configuration']['cores'][$site_id . '_index'] = [
          'conf_dir' => $solr_conf_dir,
        ];

        $services['solr']['configuration']['endpoints'][$site_id] = [
          'core' => $site_id . '_index',
        ];
        $solr_required = TRUE;
      }

      // Create cron entries.
      if (!empty($site['cron_spec']) && !empty($site['cron_cmd'])) {
        $io->text('Platform: cron entry');
        $platform['crons'][$site_id]['spec'] = $site['cron_spec'];
        $platform['crons'][$site_id]['cmd'] = $site['cron_cmd'];
      }

      if ($site['status'] !== 'production') {
        // Create Platform SH route.
        $platform_routes['https://www.' . $site['url'] . '/'] = [
          'type' => 'upstream',
          'upstream' => $platform['name'] . ':http',
          'cache' => [
            'enabled' => 'false',
          ],
        ];

        $platform_routes['https://' . $site['url'] . '/'] = [
          'type' => 'redirect',
          'to' => 'https://www.' . $site['url'] . '/',
        ];
      }

      // If a site folder doesn't exist under project/sites, create it and
      // provide a settings file.
      if (!$this->fs()->exists('/project/sites/' . $site_id)) {
        $io->text('Creating a site directory for ' . $site_id . ' under project/sites/');
        $this->fs()->mkdir('/project/sites/' . $site_id);
        $this->fs()->copy('/.lando/config/multisite.settings.php', '/project/sites/' . $site_id . '/settings.php');
      }

      // Enable our multisite entry by linking from the sites directory to
      // the project directory.
      try {
        $this->fs()->symlink('//app/project/sites/' . $site_id, '/web/sites/' . $site_id);
        $io->text('Linking sites directory');
      }
      catch (IOExceptionInterface $exception) {
        $io->error("An error occurred while linking $site_id site directory: " . $exception->getMessage());
      }

      // If a site config doesn't exist under project/config, create it.
      if (!$this->fs()->exists('/project/config/' . $site_id)) {
        $io->text('Creating config directory for ' . $site_id . ' under project/config/');
        $this->fs()->mkdir('/project/config/' . $site_id);
        $this->fs()->dumpFile('/project/config/' . $site_id . '/.gitkeep', "");

        // Create the default config directories if they don't already exist.
        foreach (['config', 'hosted', 'local', 'production'] as $directory) {
          $io->text('Creating default config directories');
          if (!$this->fs()->exists('/project/config/' . $site_id . '/' . $directory)) {
            $this->fs()->mkdir('/project/config/' . $site_id . '/' . $directory);
          }
        }
      }
    }

    $io->section('Creating hosting configuration.');

    // Update platform post deploy hook with list of sites.
    $io->text('Updating Platform post-deploy hook.');
    $platform['hooks']['post_deploy'] = str_replace('<sites_placeholder>', implode(' ', array_keys($project['sites'])), $platform['hooks']['post_deploy']);

    // Add 'Catch all' to PlatformSH routing.
    $io->text("Adding 'Catch all' to Platform routes.");
    $platform_routes['https://www.{all}/'] = [
      'type' => 'upstream',
      'upstream' => $platform['name'] . ':http',
      'cache' => [
        'enabled' => 'false',
      ],
    ];

    $platform_routes['https://{all}/'] = [
      'type' => 'redirect',
      'to' => 'https://www.{all}/',
    ];

    // Remove Solr config if none of the sites use Solr.
    if (!$solr_required) {
      unset($services['solr']);
    }

    // Write configuration files.
    $io->section('Writing configuration files');
    $platform_config = Yaml::dump($platform, 6);
    $platform_routes_config = Yaml::dump($platform_routes, 6);
    $platform_services_config = Yaml::dump($services, 6);
    $lando_config = Yaml::dump($lando, 6);

    // List of YAML configuration files to be saved.
    $config_files = [
      '/.platform.app.yaml' => [$platform_config, 'Platform app'],
      '/.lando.yml' => [$lando_config, 'Lando configuration'],
      '/.platform/routes.yaml' => [$platform_routes_config, 'Platform routes'],
      '/.platform/services.yaml' => [
        $platform_services_config,
        'Platform services',
      ],
    ];

    // Attempt to write the YAML configuration files.
    foreach ($config_files as $file => $file_data) {
      try {
        $this->fs()->dumpFile($file, $file_data[0]);
        $io->success("Created $file_data[1] file");
      }
      catch (IOExceptionInterface $exception) {
        $io->error("Unable to create $file_data[1] file, error: " . $exception->getMessage());
      }
    }

    // Copy Platform Solr server configuration.
    try {
      $this->fs()->mirror('/.hosting/platformsh/solr_config', '/.platform/solr_config');
      $io->success('Successfully copied Solr server configuration');
    }
    catch (IOExceptionInterface $exception) {
      $io->error('Unable to copy Solr server configuration, error: ' . $exception->getMessage());
    }

    // Check for an .env file and copy example if missing.
    if (!$this->fs()->exists('/.env')) {
      try {
        $this->copy('/.env.sample', '/.env');
        $io->success('Created local .env file');
      }
      catch (IOExceptionInterface $exception) {
        $io->error('Unable to create local .env file, error: ' . $exception->getMessage());
      }
    }

    // Read .env file to check for some default Drupal environment settings.
    $env_data = $this->fs()->readFile('/.env');

    if (empty($env_data['HASH_SALT'])) {
      if ($io->confirm('Hash Salt was not found in the .env file. Would you like to add one?')) {
        $env_data['HASH_SALT'] = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(random_bytes(55)));
        $this->fs()->dumpFile('/.env', $env_data);
        $io->success('Creating local site hash within .env file');
      }
    }

    // Steps to take after the project has finished building.
    $post_build_instructions = [
      "Run 'lando start' or 'lando rebuild'",
      "Download the site databases using 'platform db:dump'",
      "Import platform databases using 'lando db-import <database name> <platform dump file>'",
      "Download the site files using 'platform mount:download'",
    ];

    // Check for the vendor dir and add message to instructions.
    if (!$this->fs()->exists('/vendor')) {
      $post_build_instructions[] = "Run 'lando composer install' to install the project dependencies.";
    }

    $io->section("Finished!");
    $io->text("To build your local unity sites:");
    $io->listing($post_build_instructions);

    return Command::SUCCESS;
  }

  /**
   * Create a machine safe application ID.
   *
   * @param string $name
   *   Name of the project to create an ID for.
   *
   * @return string
   *   Machine safe application ID.
   */
  private function createApplicationId(string $name): string {
    return strtolower(str_replace(' ', '_', $name));
  }

}
