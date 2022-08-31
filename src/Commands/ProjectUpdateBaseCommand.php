<?php

namespace UnityShell\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use UnityShell\Services\MessageGenerator;
use UnityShell\Utils;

/**
 * Command pull in updates from Unity_base.
 */
class ProjectUpdateBaseCommand extends Command {

  /**
   * Defines configuration options for this command.
   */
  protected function configure(): void {
    $this->setName('project:update-base');
    $this->setDescription('Update the project with the latest changes to Unity Base');
    $this->setAliases(['pub']);
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

    $commands = [];
    $commands[] = "git fetch upstream main";
    $commands[] = "git pull --no-rebase upstream main";

    $process = new Process(implode(' && ', $commands));
    $process->setWorkingDirectory(Utils::projectRoot());
    $process->run();

    if (!$process->isSuccessful()) {
      if (str_starts_with($process->getErrorOutput(), "fatal: 'upstream' does not appear to be a git repository")) {
        $commands = [];
        $commands[] = "git remote add upstream https://github.com/dof-dss/unity_base.git";
        $commands[] = "git remote set-url --push upstream no-push";

        $process = new Process(implode(' && ', $commands));
        $process->setWorkingDirectory(Utils::projectRoot());
        $process->run();

        if (!$process->isSuccessful()) {
          $io->error("Unable to add upstream remote. Check your permissions and manually add the upstream remote by running:");
          $io->listing([
            'git remote add upstream https://github.com/dof-dss/unity_base.git',
            'git remote set-url --push upstream no-push',
          ]);
          return Command::FAILURE;
        }

        $io->success("Successfully added upstream remote to the repository.");
        $this->execute($input, $output);
      } else {
        throw new ProcessFailedException($process);
      }
    } else {
      $io->success('Update from Unity Base successful.');
      return Command::SUCCESS;
    }

    return Command::FAILURE;
  }

}
