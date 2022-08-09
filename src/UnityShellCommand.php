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

    /** Check for the existence of a file or directory.
     *
     * @param $file_path
     * @return bool
     */
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
     * Create a directory.
     *
     * @param $path
     */
    public function createDirectory($path) {
        $this->fs->mkdir($this->rootPath() . $path);
    }

    /**
     * Copy a file or directory.
     *
     * @param $original_path
     * @param $destination_path
     */
    public function copy($original_path, $destination_path) {
        // If we are dealing with a directory, copy all the contents over.
        if (empty(pathinfo($original_path, PATHINFO_FILENAME) && empty(pathinfo($original_path, PATHINFO_EXTENSION)))) {
            $this->mirror($this->rootPath() . $original_path, $this->rootPath() . $destination_path);
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

    /**
     * @param $data
     *  Array of data to be written.
     * @param $i
     *  ini file index.
     * @return string
     */
    private function writeIniFile(array $data, $i = 0){
        $str="";
        foreach ($data as $key => $val){
            if (is_array($val)) {
                $str.=str_repeat(" ",$i*2)."[$key]".PHP_EOL;
                $str.= $this->writeIniFile($val, $i+1);
            } else {
                $str.=str_repeat(" ",$i*2)."$key = $val".PHP_EOL;
            }
        }
        return $str;
    }

}