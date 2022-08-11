<?php

namespace App\Command;

use App\FileSystemDecorator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'site:foo',
    description: 'Display a list of sites for this project',
    hidden: false,
    aliases: ['sl']
)]
class SiteFoo extends Command {

    protected $fs;

    public function __construct(string $name = null) {
        parent::__construct($name);
        $this->fs = new FileSystemDecorator(new \Symfony\Component\Filesystem\Filesystem());

    }

    protected function execute(InputInterface $input, OutputInterface $output): int {

        $this->fs->foobar('/testdirectory');

        return Command::SUCCESS;

    }
}
