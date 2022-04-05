<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'hosting:build',
    description: 'Builds the hosting environment for Unity sites',
    hidden: false,
    aliases: ['hb']
)]
class HostingBuildCommand extends Command {
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'hosting:build';

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $filesystem = new Filesystem();

        // TODO: Spin most of this code out into separate functions or services
        // and remove all these todo's

        // TODO: Check we are running in the root of a Unity repo.
        // TODO: Determine the execution path and replace getcwd() calls with something better.

        $output->writeln(['Building host environment']);

        // TODO: Check existence of project dir and file.
        $project = Yaml::parseFile(getcwd() . '/project/project.yml');

        // Platform SH specific configuration.
        $platform = Yaml::parseFile(getcwd() . '/hosting/platformsh/.platform.app.template.yaml');
        $services = Yaml::parseFile(getcwd() . '/hosting/platformsh/.services.template.yaml', Yaml::PARSE_CUSTOM_TAGS);

        // Create the Platform and Lando application name.
        $platform['name'] = $this->createApplicationID($project['application_name']);
        $lando['name'] = $platform['name'];

//        $filesystem->chmod('web/sites', 0777, 0000, true);

        // TODO: Check if config exists, cleanup.
        foreach ($project['sites'] as $site_id => $site) {

            $output->writeln('Building: ' . $site_id);

            // Create Lando proxy.
            $lando['proxy']['appserver'][] = $site['url'] . '.lndo.site';
            $platform['relationships'][$site_id] = 'db:' . $site['database'];

            // Create database relationship.
            if (!empty($site['database'])) {
                $platform['relationships'][$site_id] = 'db:' . $site['database'];
            }

            // Create solr relationship.
            if (!empty($site['solr'])) {
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

            // Create cron entries.
            if (!empty($site['cron_spec']) && !empty($site['cron_cmd'])) {
                $platform['cron'][$site_id]['spec'] = $site['cron_spec'];
                $platform['cron'][$site_id]['cmd'] = $site['cron_cmd'];
            }

            // Create deployment list.
            if ($site['deploy'] === TRUE) {
                $deployed_sites[] = $site_id;
            }

            // If a site folder doesn't exist under project/sites, create it and provide a settings file.
            if (!$filesystem->exists(getcwd() . '/project/sites/' . $site_id)) {
                $output->writeln('Creating a site directory for ' . $site_id . ' under project/sites/');
                $filesystem->mkdir(getcwd() .  '/project/sites/' . $site_id);
                $filesystem->copy(getcwd() . '/.lando/config/multisite.settings.php', getcwd() . '/project/sites/' . $site_id . '/settings.php' );
            }

            // Enable our multisite entry by linking from the sites dir to the project dir.
            try {
                $filesystem->symlink('/app/project/sites/' . $site_id, 'web/sites/' . $site_id);
            } catch (IOExceptionInterface $exception) {
                $output->writeln("An error occurred while linking $site_id site directory: " . $exception->getMessage());
            }

            // If a site config doesn't exist under project/config, create it.
            if (!$filesystem->exists(getcwd() . '/project/config/' . $site_id)) {
                $output->writeln('Creating config directory for ' . $site_id . ' under project/config/');
                $filesystem->mkdir(getcwd() .  '/project/config/' . $site_id);
                $filesystem->touch(getcwd() .  '/project/config/' . $site_id . '/.gitkeep');
            }

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

            // Create Platform SH services.
            $services['db']['configuration']['schemas'][] = $site_id;
            $services['db']['configuration']['endpoints'][$site_id] = [
                'default_schema' => $site_id . 'db',
                'privileges' => [
                    $site_id . 'db' => 'admin'
                ],
            ];

            if (!empty($site['solr'])) {
                $services['solr']['configuration']['cores'][$site_id . '_index'] = [
                    'conf_dir' => '!archive "solr_config/"',
                ];
                $services['solr']['configuration']['endpoints'][$site_id] = [
                    'core' => $site_id . '_index',
                ];
            }

        }

        // Update platform post deploy hook with list of deployed sites.
        $platform['hooks']['post_deploy'] = str_replace('<deployed_sites_placeholder>', implode(' ', $deployed_sites), $platform['hooks']['post_deploy']);

        $platform_config = Yaml::dump($platform, 2);
        $platform_routes_config = Yaml::dump($platform_routes, 2);
        $platform_services_config = Yaml::dump($services, 6);
        $lando_config = Yaml::dump($lando, 2);

        file_put_contents(getcwd() . '/.platform.app.yaml', $platform_config);
        file_put_contents(getcwd() . '/.lando.yml', $lando_config);
        file_put_contents(getcwd() . '/.platform/routes.yaml', $platform_routes_config);
        file_put_contents(getcwd() . '/.platform/services.yaml', $platform_services_config);

        // Check for an .env file and copy example if missing
        if (!$filesystem->exists(getcwd() .'/.env')) {
            $filesystem->copy(getcwd() . '/.env.sample', getcwd() . '/.env');
        }


        $env_data = parse_ini_file(getcwd() .'/.env');

        if (empty($env_data['HASH_SALT'])) {
            $question = new ConfirmationQuestion('Hash Salt was not found in the .env file. Would you like to add one? (Y/n)', true);
            $helper = $this->getHelper('question');

            if ($helper->ask($input, $output, $question)) {
                $env_data['HASH_SALT'] = str_replace(['+', '/', '=',], ['-', '_', '',], base64_encode(random_bytes(55)));
                $this->writeIniFile(getcwd() .'/.env', $env_data);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Create a machine safe application id.
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