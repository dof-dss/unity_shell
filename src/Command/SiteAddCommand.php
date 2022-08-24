<?php

namespace UnityShell\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Command to add a site to a Unity2 project.
 */
class SiteAddCommand extends Command {

  /**
   * Defines configuration options for this command.
   */
  protected function configure(): void {
    $this->setName('site:add');
    $this->setDescription('Add a new site to the project');
    $this->setAliases(['sa']);

    $this->addArgument('siteid', InputArgument::OPTIONAL, 'Site ID (Must be a machine name e.g. uregni)');
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

    $site_id = $input->getArgument('siteid');

    if (empty($site_id)) {
      $site_id = $io->ask('Please provide a site ID (e.g. uregni)');
      if (empty($site_id)) {
        $io->error('Site ID not given');
        return Command::FAILURE;
      }
    }

    // Unity2 Project file.
    $project = $this->fs()->readFile('/project/project.yml');

    // @todo Check if a site with that ID exists.
    $site['name'] = $io->ask('Site name');
    $site['url'] = $io->ask('Site URL (minus the protocol and trailing slash');
    if ($io->confirm('Does this site require a Solr search?')) {
      $site['solr'] = $site_id;
    }

    // @todo Prompt if user would like to use cron defaults.
    $site['cron_spec'] = '10 * * * *';
    $site['cron_cmd'] = 'cd web/sites/' . $site_id . ' ; drush core-cron';

    $site['database'] = $site_id;

    $helper = $this->getHelper('question');
    $site_status_list = new ChoiceQuestion(
      'Please select the site status',
      self::SITE_STATUS,
      0
    );

    $site_status_list->setErrorMessage('Status %s is invalid.');

    $site_status = $helper->ask($input, $output, $site_status_list);
    $site['status'] = $site_status;

    $project['sites'][$site_id] = $site;

    $project_config = Yaml::dump($project, 6);

    try {
      $this->fs()->dumpFile('/project/project.yml', $project_config);
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