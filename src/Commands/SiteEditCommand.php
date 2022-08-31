<?php

namespace UnityShell\Commands;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Command to edit a site within a Unity2 project.
 */
class SiteEditCommand extends Command {

  /**
   * Defines configuration options for this command.
   */
  protected function configure(): void {
    $this->setName('site:edit');
    $this->setDescription('Edit a site in the project');
    $this->setAliases(['se']);

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

    // @todo Warn if we have no site entries.
    // Provide a list of sites from the project file for the user to select.
    if (empty($site_id)) {
      $site_options = ['Cancel'];
      $site_options = array_merge($site_options, array_keys($this->project()->sites()));

      $helper = $this->getHelper('question');
      $sites_choice_list = new ChoiceQuestion(
        'Please select a site to edit',
        $site_options,
        0
      );
      $sites_choice_list->setErrorMessage('Site %s is invalid.');

      $site_id = $helper->ask($input, $output, $sites_choice_list);

      if ($site_id === 'Cancel') {
        $io->info('Cancelling site edit.');
        return Command::SUCCESS;
      }
    }

    if (!array_key_exists($site_id, $this->project()->sites())) {
      throw new \InvalidArgumentException("Site ID '$site_id' does not exist in the project.");
    }

    $site_current = $this->project()->sites()[$site_id];
    $site['name'] = $io->ask('Site name', $site_current['name']);
    $site['url'] = $io->ask('Site URL (minus the protocol and trailing slash', $site_current['url']);
    if ($io->confirm('Does this site require a Solr search?')) {
      $site['solr'] = $site_id;
    }

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

    $this->project()->updateSite($site_id, $site);

    if ($io->confirm('Would you like to rebuild the project?')) {
      $build_command = $this->getApplication()->find('project:build');
      return $build_command->run(new ArrayInput([]), $output);
    }
    return Command::FAILURE;
  }

}
