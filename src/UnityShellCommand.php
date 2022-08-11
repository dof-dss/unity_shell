<?php

namespace App;

use App\Command\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'unity:shell',
    description: 'Base command for Unity Shell',
    hidden: true,
)]
class UnityShellCommand extends Command {

    protected static $defaultName = 'unity:shell';
    private FileSystemDecorator $fs;

    public function __construct(string $name = null) {
        parent::__construct($name);

        $this->fs = new FileSystemDecorator(new Filesystem());
    }

    /**
     * FileSystem getter.
     *
     * @return FileSystemDecorator
     */
    public function fs() {
        return $this->fs;
    }

}