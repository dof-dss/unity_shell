<?php

namespace App\Command;

use App\UnityShellCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(
  name: 'site:list',
  description: 'Display a list of sites for this project',
  hidden: FALSE,
  aliases: ['sl']
)]
/**
 * Command to list sites witing a Unity2 project.
 */
class SiteListCommand extends UnityShellCommand {

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
    $rows = [];

    // Unity2 Project file.
    $project = $this->fs()->readFile('/project/project.yml');

    $cell_green = new TableCellStyle([
      'fg' => 'black',
      'bg' => 'green',
    ]);
    $cell_yellow = new TableCellStyle([
      'fg' => 'black',
      'bg' => 'yellow',
    ]);

    foreach ($project['sites'] as $site) {

      if ($site['status'] === 'production') {
        $status = new TableCell($site['status'], ['style' => $cell_green]);
      } else {
        $status = new TableCell($site['status'], ['style' => $cell_yellow]);
      }

      $rows[] = [
        $site['name'],
        $site['url'],
        $site['database'],
        (empty($site['solr'])) ? 'No' : 'Yes',
        $status,
      ];
    }

    $table = new Table($output);
    $table->setStyle('box-double');
    $table->setHeaderTitle($project['project_name'] . ' (' . $project['project_id'] . ')');
    $table->setHeaders(['Name', 'URL', 'Database', 'Solr', 'Status'])
      ->setRows($rows);
    $table->render();

    return Command::SUCCESS;
  }

}
