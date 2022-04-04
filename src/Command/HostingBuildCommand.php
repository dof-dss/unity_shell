<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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

        // Check for an .env file and copy example if missing
        if (!$filesystem->exists(getcwd() .'/.env')) {
            $filesystem->copy(getcwd() . '/.env.sample', getcwd() . '/.env');
        }

        // TODO: Check existence of project dir and file.
        $project = Yaml::parseFile(getcwd() . '/project/project.yml');

        $platform = Yaml::parseFile(getcwd() . '/.platform/.platform.app.yaml');

        // Create the Platform and Lando application name.
        $platform['name'] = $this->createApplicationID($project['application_name']);
        $lando['name'] = $platform['name'];

//        $filesystem->chmod('web/sites', 0777, 0000, true);

        // TODO: Check if config exists, cleanup.
        foreach ($project['sites'] as $site_id => $site) {

            $output->writeln('Building: ' . $site_id);

            // Create Lando proxy.
            $lando['proxy']['appserver'][] = $site_id . '.lndo.site';
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

            // Create symlinks to sites
            // TODO: If directory exists, check type and replace with symlink if needed.
            try {
                $filesystem->symlink('project/sites/' . $site_id, 'web/sites/' . $site_id);
            } catch (IOExceptionInterface $exception) {
                $output->writeln("An error occurred while linking $site_id site directory: " . $exception->getMessage());
            }
        }

        // Update platform post deploy hook with list of deployed sites.
        $platform['hooks']['post_deploy'] = str_replace('<deployed_sites_placeholder>', implode(' ', $deployed_sites), $platform['hooks']['post_deploy']);

        $platform_config = Yaml::dump($platform, 2);
        $lando_config = Yaml::dump($lando, 2);

        file_put_contents(getcwd() . '/.platform.app.yaml', $platform_config);
        file_put_contents(getcwd() . '/.lando.yml', $lando_config);

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

}