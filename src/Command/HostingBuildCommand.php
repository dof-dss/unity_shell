<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'hosting:build',
    description: 'Builds the hosting environment for Unity sites',
    hidden: false,
    aliases: ['hb']
)]
class HostingBuildCommand extends Command {
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'hosting:build';

    protected function execute(InputInterface $input, OutputInterface $output): int {

        $output->writeln(['Building host environment']);
        return Command::SUCCESS;
    }
}