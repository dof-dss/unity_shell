<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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

        $output->writeln(['Building host environment']);

        // TODO: Check existence of project dir and file.
        $project = Yaml::parseFile(getcwd() . '/project/project.yml');

        $platform = Yaml::parseFile(getcwd() . '/.platform/.platform.app.yaml');

        // TODO: Check if config exists, cleanup.
        foreach ($project['sites'] as $site_id => $site) {

            $output->writeln('Building: ' . $site_id);

            // Create database relationship.
            if (!empty($site['database'])) {
                $platform['relationships'][$site_id] = 'db:' . $site['database'];
            }

            // Create solr relationship.
            if (!empty($site['solr'])) {
                $platform['relationships'][$site_id . '_solr'] = 'solr:' . $site['solr'];
            }
        }

        $platform_app = Yaml::dump($platform);

        file_put_contents(getcwd() . '/.platform.app.yaml', $platform_app);

        return Command::SUCCESS;
    }
}