<?php

namespace App\Command;

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
    name: 'project:create',
    description: 'Create a new Unity project',
    hidden: false,
    aliases: ['pc']
)]
class ProjectCreateCommand extends Command {
    protected function configure(): void {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Project name');
        $this->addArgument('id', InputArgument::OPTIONAL, 'PlatformSH project ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        $project_name = $input->getArgument('name');

        if (empty($project_name)) {
            $project_name = $io->ask('Please provide a project name (Human readable)');
            if (empty($project_name)) {
                $io->error('Project name not given');
                return Command::FAILURE;
            }
        }

        $project_id = $input->getArgument('id');

        if (empty($project_id)) {
            $project_id = $io->ask('Please provide a PlatformSH project ID');
            if (empty($project_id)) {
                $io->error('Project ID not given');
                return Command::FAILURE;
            }
        }

        if (!$filesystem->exists(getcwd() . '/project')) {
            $filesystem->mkdir(getcwd() . '/project');
            $filesystem->mkdir(getcwd() . '/project/config');
            $filesystem->mkdir(getcwd() . '/project/sites');
            $io->info('Creating project directory.');
        }

        $project['project_name'] = $project_name;
        $project['project_id'] = $project_id;

        $project_config = Yaml::dump($project, 6);

        try {
            $filesystem->dumpFile(getcwd() . '/project/project.yml', $project_config);
            $io->success('Created project file');

            if($io->confirm('Would you like to add a site to the project?')) {
                $build_command = $this->getApplication()->find('site:add');

                $return_code = $build_command->run(new ArrayInput([]), $output);
                return $return_code;
            } else {
                return Command::SUCCESS;
            }

            return Command::SUCCESS;
        }
        catch (IOExceptionInterface $exception) {
            $io->error('Unable to create Project file, error: ' . $exception->getMessage());
            return Command::FAILURE;
        }
    }
}
