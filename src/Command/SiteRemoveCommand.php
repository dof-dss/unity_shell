<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'site:remove',
    description: 'Remove a site from the project',
    hidden: false,
    aliases: ['sa']
)]
class SiteRemoveCommand extends Command {

    protected function configure(): void {
        $this->addArgument('siteid', InputArgument::REQUIRED, 'Site ID (Must be a machine name e.g. uregni)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        $site_id = $input->getArgument('siteid');

        if (empty($site_id)) {
            $site_id = $io->ask('Please provide a site ID (e.g. uregni)');
            if (empty($site_id)) {
                $io->error('Site ID not given');
                return Command::FAILURE;
            }
        }

        // TODO: Provide a list of sites from the project file.

        $project = Yaml::parseFile(getcwd() . '/project/project.yml');

        if (array_key_exists($site_id, $project['sites'])) {
            unset($project['sites'][$site_id]);

            $project_config = Yaml::dump($project, 6);

            try {
                $filesystem->dumpFile(getcwd() . '/project/project.yml', $project_config);
                $io->success('Successfully removed ' . $site_id . ' from the project.');
                return Command::SUCCESS;
            }
            catch (IOExceptionInterface $exception) {
                $io->error('Unable to update Project file, error: ' . $exception->getMessage());
                return Command::FAILURE;
            }
        } else {
            $io->error('Site ' . $site_id . ' not found within Projects file.');
            return Command::FAILURE;
        }
    }
}
