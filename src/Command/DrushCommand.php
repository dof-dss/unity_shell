<?php

namespace App\Command;

use App\UnityShellCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(
  name: 'drush',
  description: 'Run drush from within a Unity project',
  hidden: FALSE,
  aliases: ['drh']
)]
/**
 * Command to run drush within a Unity2 project.
 */
class DrushCommand extends UnityShellCommand {

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

    // Unity2 Project file.
    $project = $this->fs()->readFile('/project/project.yml');
    $sites = $project['sites'];

    // Current path within the scope of the project
    $current_project_path = substr(getcwd(), strlen($this->fs()->projectRoot()));

    // Look at each directory and try to match against a site id.
    foreach (explode('/', $current_project_path) as $directory) {
      if (array_key_exists($directory, $sites)) {
      }
    }

    return Command::SUCCESS;
  }

}
