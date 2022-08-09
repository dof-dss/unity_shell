<?php

namespace App\Command;

use App\UnityShellCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'site:add',
    description: 'Add a new site to the project',
    hidden: false,
    aliases: ['sa']
)]
class SiteAddCommand extends UnityShellCommand {
    protected function configure(): void {
        $this->addArgument('siteid', InputArgument::OPTIONAL, 'Site ID (Must be a machine name e.g. uregni)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        $site_id = $input->getArgument('siteid');

        if (empty($site_id)) {
            $site_id = $io->ask('Please provide a site ID (e.g. uregni)');
            if (empty($site_id)) {
                $io->error('Site ID not given');
                return Command::FAILURE;
            }
        }

        // Unity2 Project file.
        $project = $this->fileRead('/project/project.yml');

        // TODO: Check if a site with that ID exists.

        $site['name'] = $io->ask('Site name');
        $site['url'] = $io->ask('Site URL (minus the protocol and trailing slash');
        if ($io->confirm('Does this site require a Solr search?')) {
            $site['solr'] = $site_id;
        }

        // TODO: Prompt if user would like to use cron defaults.

        $site['cron_spec'] = '10 * * * *';
        $site['cron_cmd'] = 'cd web/sites/' . $site_id . ' ; drush core-cron';

        $site['database'] = $site_id;
        $site['deploy'] = false;

        if ($io->confirm('Do you want this project deployed live on Platform?')) {
            $site['deploy'] = true;
        }

        $project['sites'][$site_id] = $site;

        $project_config = Yaml::dump($project, 6);

        try {
            $this->fileWrite('/project/project.yml', $project_config);
            $io->success('Updated project file');

            $io->section('Site details for: ' . $site_id);
            foreach ($site as $property => $value) {
                $io->writeln($property . ' : ' . $value);
            }

            if ($io->confirm('Would you like to rebuild the project?')) {
                $build_command = $this->getApplication()->find('project:build');

                $return_code = $build_command->run(new ArrayInput([]), $output);
                return $return_code;
            }

            $io->success('Successfully added ' . $site_id . ' to the project.');
            return Command::SUCCESS;
        }
        catch (IOExceptionInterface $exception) {
            $io->error('Unable to update Project file, error: ' . $exception->getMessage());
            return Command::FAILURE;
        }
    }
}
