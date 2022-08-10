<?php

namespace App;

use DrupalFinder\DrupalFinder;

class FileSystemDecorator {

    protected $fs;
    protected string $projectRoot;

    public function __construct($file_system) {
        $this->fs = $file_system;

        $drupalFinder = new DrupalFinder();
        $drupalFinder->locateRoot(getcwd());
        $this->projectRoot = $drupalFinder->getComposerRoot();
    }

    public function __call($method, $args) {
        // Pretty basic way to determine if the arg is a file/dir path.
        // Could do with a lot of improvement.
        foreach ($args as $index => $val) {
            if (is_string($val) && str_starts_with($val, '/')) {
                $args[$index] = $this->projectRoot . $val;
            }
        }

        if (is_callable($this->fs, $method)) {
            return call_user_func_array(array($this->fs, $method), $args);
        }
        throw new \Exception('Undefined method: ' . get_class($this->fs) . '::' . $method);
    }


}