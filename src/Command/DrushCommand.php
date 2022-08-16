<?php

namespace App\Command;

use App\UnityShellCommand;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;

#[AsCommand(
  name: 'drush',
  description: 'Run drush from within a Unity project',
  hidden: FALSE,
  aliases: ['dr']
)]
/**
 * Command to run drush within a Unity2 project.
 */
class DrushCommand extends UnityShellCommand {

  protected function configure() {
    $this->ignoreValidationErrors();

    $this->addArgument('cmd', InputArgument::REQUIRED, 'Drush command');
    $this->addOption('uri', 'l', InputArgument::OPTIONAL, "Site URI");
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

    // Unity2 Project file.
    $project = $this->fs()->readFile('/project/project.yml');
    $sites = $project['sites'];

    // Current path within the scope of the project
    $current_project_path = substr(getcwd(), strlen($this->fs()->projectRoot()));

    if (empty($input->getOption('uri'))) {
      // Look at each directory and try to match against a site id.
      foreach (explode('/', $current_project_path) as $directory) {
        if (array_key_exists($directory, $sites)) {
          $input->setOption('uri', $directory);
        }
      }
    }

    $command[] = 'lando';
    $command[] = 'drush';
    $command[] = $input->getArgument('cmd');
    $command[] .= ($input->hasOption('uri')) ? '-l ' . $input->getOption('uri') : '';

    $process = new Process($command);
    $process->run(function ($type, $buffer) {
      if (Process::ERR === $type) {
        echo 'ERR > '.$buffer;
      } else {
        echo 'OUT > '.$buffer;
      }
    });

    return Command::SUCCESS;
  }

}
