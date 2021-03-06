<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'project:build',
    description: 'Builds Lando and Platform hosting environments for this project',
    hidden: false,
    aliases: ['pb']
)]
class ProjectBuildCommand extends Command {
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'project:build';

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $filesystem = new Filesystem();
        $io = new SymfonyStyle($input, $output);
        $solr_required = FALSE;
        // List of deployed PlatformSH sites.
        $deployed_sites = [];

        // TODO: Spin most of this code out into separate functions or services
        // and remove all these todo's.
        // - Display notice when a site is removed and there are files in the project/site_id folder
        // - Remove all config if an entry is removed from project.yml.
        // - Replace getcwd() paths with something more succinct.
        // - Improve error handling.
        // - Download Platform databases.

        // Check we are running in the root of a Unity repo and have a project file.
        if (!$filesystem->exists(getcwd() . '/project/project.yml')) {
            $io->error('Please ensure you are in the root of a Unity project and that project/project.yml exists before running this command.');
            return Command::FAILURE;
        }

        $io->title('Building host environment');

        $project = Yaml::parseFile(getcwd() . '/project/project.yml');

        // Platform SH specific configuration.
        $platform = Yaml::parseFile(getcwd() . '/.hosting/platformsh/.platform.app.template.yaml');
        $services = Yaml::parseFile(getcwd() . '/.hosting/platformsh/.services.template.yaml');

        // Create the Platform and Lando application name.
        $platform['name'] = $this->createApplicationID($project['project_name']);
        $lando['name'] = $platform['name'];

        if (empty($project['sites'])) {
            $io->warning('This project does not have any sites defined, please add some using site:add before running this command.');
            return Command::INVALID;
        }

        // TODO: Check if config exists, cleanup.
        foreach ($project['sites'] as $site_id => $site) {

            $io->section('Creating Lando and Platform SH configuration for: '  . $site_id);

            // Create Lando proxy.
            $io->text('Lando: proxy entry');
            $lando['proxy']['appserver'][] = $site['url'] . '.lndo.site';

            // Create deployment list.
            if ($site['deploy'] === TRUE) {
                $io->text('Platform: deploy site, TRUE');
                $deployed_sites[] = $site_id;
            } else {
                $io->text('Platform: deploy site, FALSE');
            }

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
                    'portforward' => true,
                    'core' => 'default',
                    'config' => [
                        'dir' => '.lando/config/solr/7.x/default'
                    ],
                ];
            }

            // Create Platform SH services.
            $io->text('Platform: database config');
            $services['db']['configuration']['schemas'][] = $site_id . 'db';
            $services['db']['configuration']['endpoints'][$site_id] = [
                'default_schema' => $site_id . 'db',
                'privileges' => [
                    $site_id . 'db' => 'admin'
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

            if (!(bool) ($site['deploy'])) {
                // Create Platform SH route.
                $platform_routes['https://www.' . $site['url'] . '/'] = [
                    'type' => 'upstream',
                    'upstream' => $platform['name'] . ':http',
                    'cache' => [
                        'enabled' => 'false'
                    ],
                ];

                $platform_routes['https://' . $site['url'] . '/'] = [
                    'type' => 'redirect',
                    'to' => 'https://www.' . $site['url'] . '/',
                ];
            }

            // If a site folder doesn't exist under project/sites, create it and provide a settings file.
            if (!$filesystem->exists(getcwd() . '/project/sites/' . $site_id)) {
                $io->text('Creating a site directory for ' . $site_id . ' under project/sites/');
                $filesystem->mkdir(getcwd() .  '/project/sites/' . $site_id);
                $filesystem->copy(getcwd() . '/.lando/config/multisite.settings.php', getcwd() . '/project/sites/' . $site_id . '/settings.php' );
            }

            // Enable our multisite entry by linking from the sites dir to the project dir.
            try {
                $filesystem->symlink('/app/project/sites/' . $site_id, 'web/sites/' . $site_id);
                $io->text('Linking sites directory');
            } catch (IOExceptionInterface $exception) {
                $io->error("An error occurred while linking $site_id site directory: " . $exception->getMessage());
            }

            // If a site config doesn't exist under project/config, create it.
            if (!$filesystem->exists(getcwd() . '/project/config/' . $site_id)) {
                $io->text('Creating config directory for ' . $site_id . ' under project/config/');
                $filesystem->mkdir(getcwd() .  '/project/config/' . $site_id);
                $filesystem->touch(getcwd() .  '/project/config/' . $site_id . '/.gitkeep');

                // Create the default config directories if they don't already exist.
                foreach (['config', 'hosted', 'local', 'production'] as $directory) {
                    $io->text('Creating default config directories');
                    if (!$filesystem->exists(getcwd() . '/project/config/' . $site_id . '/config/' . $directory)) {
                        $filesystem->mkdir(getcwd() . '/project/config/' . $site_id . '/config/' . $directory);
                    }
                }
            }
        }

        // Update platform post deploy hook with list of deployed sites.
        $io->text('Updating Platform post-deploy hook');
        $platform['hooks']['post_deploy'] = str_replace('<deployed_sites_placeholder>', implode(' ', $deployed_sites), $platform['hooks']['post_deploy']);

        // Add 'Catch all' to PlatformSH routing.
        $io->text("Adding 'Catch all' to Platform routes.");
        $platform_routes['https://www.{all}/'] = [
            'type' => 'upstream',
            'upstream' => $platform['name'] . ':http',
            'cache' => [
                'enabled' => 'false'
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
            '/.platform/services.yaml' => [$platform_services_config, 'Platform services'],
        ];

        // Attempt to write the YAML configuration files.
        foreach ($config_files as $file => $file_data) {
            try {
                $filesystem->dumpFile(getcwd() . $file, $file_data[0]);
                $io->success("Created $file_data[1] file");
            }
            catch (IOExceptionInterface $exception) {
                $io->error("Unable to create $file_data[1] file, error: " . $exception->getMessage());
            }
        }

        // Copy Platform Solr server configuration.
        try {
            $filesystem->mirror(getcwd() . '/.hosting/platformsh/solr_config', getcwd() . '/.platform/solr_config');
            $io->success('Successfully copied Solr server configuration');
        }
        catch (IOExceptionInterface $exception) {
            $io->error('Unable to copy Solr server configuration, error: ' . $exception->getMessage());
        }

        // Check for an .env file and copy example if missing
        if (!$filesystem->exists(getcwd() .'/.env')) {
            try {
                $filesystem->copy(getcwd() . '/.env.sample', getcwd() . '/.env');
                $io->success('Created local .env file');
            }
            catch (IOExceptionInterface $exception) {
                $io->error('Unable to create local .env file, error: ' . $exception->getMessage());
            }
        }

        $env_data = parse_ini_file(getcwd() .'/.env');

        if (empty($env_data['HASH_SALT'])) {
            if($io->confirm('Hash Salt was not found in the .env file. Would you like to add one?')) {
                $env_data['HASH_SALT'] = str_replace(['+', '/', '=',], ['-', '_', '',], base64_encode(random_bytes(55)));
                if ($this->writeIniFile(getcwd() .'/.env', $env_data)) {
                    $io->success('Creating local site hash within .env file');
                } else {
                    $io->error('Unable to create local site hash within .env file');
                }
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
        if (!$filesystem->exists(getcwd() .'/vendor')) {
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
     * @return string
     */
   private function createApplicationID(string $name): string {
       return strtolower(str_replace(' ', '_', $name));
   }

    /**
     * @param $file
     *  File path to write the ini data to.
     * @param $array
     *  Array of data to be written
     * @param $i
     * @return false|int|string
     */
   private function writeIniFile(string $file, array $array, $i = 0){
        $str="";
        foreach ($array as $k => $v){
            if (is_array($v)) {
                $str.=str_repeat(" ",$i*2)."[$k]".PHP_EOL;
                $str.= $this->writeIniFile("",$v, $i+1);
            } else {
                $str.=str_repeat(" ",$i*2)."$k = $v".PHP_EOL;
            }
        }
       return file_put_contents($file, $str);
    }

}