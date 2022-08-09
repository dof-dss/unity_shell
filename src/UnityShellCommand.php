<?php

namespace App;

use App\Command\AsCommand;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'unity:shell',
    description: 'Base command for Unity Shell',
    hidden: true,
)]
class UnityShellCommand extends Command {

    protected static $defaultName = 'unity:shell';

    private string $projectRoot;
    private Filesystem $fs;

    public function __construct(string $name = null) {
        parent::__construct($name);

        $this->fs = new Filesystem();

        $drupalFinder = new DrupalFinder();
        $drupalFinder->locateRoot(getcwd());
        $this->projectRoot = $drupalFinder->getComposerRoot();
    }

    public function rootPath() {
        return $this->projectRoot;
    }

    public function fileExists($file_path) {
        return $this->fs->exists($this->rootPath() . $file_path);
    }

    /**
     * Read and parse contents of multiple file types.
     *
     * @param $file_path
     * @return array|false|mixed|string|null
     */
    public function fileRead($file_path) {
        if (!$this->fileExists($file_path)) {
            return null;
        }

        switch (pathinfo($file_path, PATHINFO_EXTENSION)) {
            case 'yaml':
            case 'yml':
                return Yaml::parseFile($this->rootPath() . $file_path);
            case '.env':
                return parse_ini_file($this->rootPath() . $file_path);
            default:
                return file_get_contents($this->rootPath() . $file_path);
        }
    }

    public function fileWrite($file_path) {

    }

}