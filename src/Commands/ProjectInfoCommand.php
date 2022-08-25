<?php

namespace UnityShell\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;
use UnityShell\Services\MessageGenerator;

/**
 * Command to display information about a Unity2 project.
 */
class ProjectInfoCommand extends Command {

  /**
   * Defines configuration options for this command.
   */
  protected function configure(): void {
    $this->setName('project:info');
    $this->setDescription('Displays information including sites this project');
    $this->setAliases(['pi']);
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
    $rows = [];

    foreach ($this->project()->sites() as $site) {
      $rows[] = [
        $site['name'],
        $site['url'],
        $site['database'],
        (empty($site['solr'])) ? 'No' : 'Yes',
        $site['status'],
      ];
    }

    $io->title($this->project()->name() . ' (' . $this->project()->id() . ') - ' . count($this->project()->sites()) . " site(s)");
    $table = new Table($output);
    $table->setHeaders(['Name', 'URL', 'Database', 'Solr', 'Status'])
      ->setRows($rows)
      ->render();

    return Command::SUCCESS;
  }

}
