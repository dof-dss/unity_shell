<?php

namespace UnityShell\Commands;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Command to remove a site to a Unity2 project.
 */
class SiteRemoveCommand extends Command {

  /**
   * Defines configuration options for this command.
   */
  protected function configure(): void {
    $this->setName('site:remove');
    $this->setDescription('Remove a site from the project');
    $this->setAliases(['sr']);

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

    // Provide a list of sites from the project file for the user to select.
    if (empty($site_id)) {
      $site_options = ['Cancel'];
      $site_options = array_merge($site_options, array_keys($this->project()->sites()));

      $helper = $this->getHelper('question');
      $sites_choice_list = new ChoiceQuestion(
        'Please select a site to remove',
        $site_options,
        0
      );
      $sites_choice_list->setErrorMessage('Site %s is invalid.');

      $site_id = $helper->ask($input, $output, $sites_choice_list);

      if ($site_id === 'Cancel') {
        $io->success('Cancelling site removal.');
        return Command::SUCCESS;
      }
    }

    $this->project()->removeSite($site_id);

    if ($io->confirm('Would you like to rebuild the project?')) {
      $build_command = $this->getApplication()->find('project:build');
      return $build_command->run(new ArrayInput([]), $output);
    }

    $io->success('Successfully removed ' . $site_id . ' from the project.');
    return Command::SUCCESS;
  }

}
