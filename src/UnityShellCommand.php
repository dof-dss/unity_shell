<?php

namespace App;

use App\Command\AsCommand;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Console\Command\Command;
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
    private FileSystemDecorator $fs;

    public function __construct(string $name = null) {
        parent::__construct($name);

        $this->fs = new FileSystemDecorator(new Filesystem());
    }

    public function fs() {
        return $this->fs;
    }

    /** Check for the existence of a file or directory.
     *
     * @param $file_path
     * @return bool
     */
    public function fileExists($file_path) {
        return $this->fs->exists($this->rootPath() . $file_path);
    }



    /**
     * Write content to a file.
     *
     * @param $file_path
     */
    public function fileWrite($file_path, $contents) {
        if (str_ends_with($file_path, '.env')) {
            $contents = $this->writeIniFile($contents);
        }

        $this->fs->dumpFile($this->rootPath() . $file_path, $contents);
    }


    /**
     * Copy a file or directory.
     *
     * @param $original_path
     * @param $destination_path
     */
    public function copy($original_path, $destination_path) {
        // Directory detection is not ideal but is_dir() would fail on some
        // calls. Unfortunately files without an extension will be treated as
        // directories.
        if (empty(pathinfo($original_path, PATHINFO_EXTENSION))) {
            $this->fs->mirror($this->rootPath() . $original_path, $this->rootPath() . $destination_path);
        } else {
            $this->fs->copy($this->rootPath() . $original_path, $this->rootPath() . $destination_path);
        }
    }

    /**
     * Remove a file or directory.
     *
     * @param $path
     */
    public function remove($path) {
        $this->fs->remove($this->rootPath() . $path);
    }

    /*
     * Return the Filesystem object.
     */
    protected function fileSystem() {
        return $this->fs;
    }



}