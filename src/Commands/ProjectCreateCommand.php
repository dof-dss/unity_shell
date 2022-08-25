<?php

namespace UnityShell\Commands;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Command to create a Unity2 project.
 */
class ProjectCreateCommand extends Command {

  /**
   * Defines configuration options for this command.
   */
  protected function configure(): void {
    $this->setName('project:create');
    $this->setDescription('Create a new Unity project');
    $this->setAliases(['pc']);

    $this->addArgument('name', InputArgument::OPTIONAL, 'Project name');
    $this->addArgument('id', InputArgument::OPTIONAL, 'PlatformSH project ID');
  }

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

    if (!$this->fs()->exists('/project')) {
      $this->fs()->mkdir('/project');
      $this->fs()->mkdir('/project/config');
      $this->fs()->mkdir('/project/sites');
      $io->info('Creating project directory.');
    }

    $project['project_name'] = $project_name;
    $project['project_id'] = $project_id;

    $project_config = Yaml::dump($project, 6);

    try {
      $this->fs()->dumpFile('/project/project.yml', $project_config);
      $io->success('Created project file');

      if ($io->confirm('Would you like to add a site to the project?')) {
        $build_command = $this->getApplication()->find('site:add');

        $return_code = $build_command->run(new ArrayInput([]), $output);
        return $return_code;
      }
      return Command::SUCCESS;
    }
    catch (IOExceptionInterface $exception) {
      $io->error('Unable to create Project file, error: ' . $exception->getMessage());
      return Command::FAILURE;
    }
  }

}
