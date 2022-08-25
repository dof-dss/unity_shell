<?php

namespace UnityShell;

use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Decorator for the symfony FileSystem component.
 *
 * This decorator mirrors the FileSystem component but also provides
 * custom methods for reading and writing files.
 *
 * See: https://symfony.com/doc/current/components/filesystem.html
 *
 * By default, paths passed to these methods will be prefixed with the current
 * Unity project root path. If you require an absolute path you should start
 * the path with a double slash (e.g. //app/project/sites).
 */
class FileSystemDecorator {

  /**
   * The FileSystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * The project root path.
   *
   * @var string|bool
   */
  protected string $projectRoot;

  /**
   * FileSystemDecorator constructor.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $file_system
   *   Filesystem component.
   */
  public function __construct(Filesystem $file_system) {
    $this->fs = $file_system;

    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $this->projectRoot = $drupalFinder->getComposerRoot();
  }

  /**
   * Magic method to process calls before passing on.
   *
   * @param string $method
   *   The method to call.
   * @param array $args
   *   The method parameter arguments.
   *
   * @return array|false|mixed|string|null
   *   Returns data from the magic methods call.
   *
   * @throws \Exception
   */
  public function __call(string $method, array $args) {
    // Pretty basic way to determine if the arg is a file/dir path.
    // Could do with a lot of improvement.
    foreach ($args as $index => $val) {
      // If a path starts with a double slash, do not prepend the
      // project root path and remove the leading slash.
      if (is_string($val) && str_starts_with($val, '//')) {
        $args[$index] = substr($val, 1, strlen($val));
      }
      elseif (is_string($val) && str_starts_with($val, '/')) {
        $args[$index] = $this->projectRoot . $val;
      }
    }

    if ($method == 'readFile') {
      return $this->readFile($args);
    }

    if ($method == 'dumpFile') {
      return call_user_func_array([$this, 'dumpFile'], $args);
    }

    if (is_callable([$this->fs, $method])) {
      return call_user_func_array([$this->fs, $method], $args);
    }
    throw new \Exception('Undefined method: ' . get_class($this->fs) . '::' . $method);
  }

  /**
   * Read and parse contents of multiple file types.
   *
   * @param string $file_path
   *   System path to read from.
   *
   * @return array|false|mixed|string|null
   *   File contents.
   */
  protected function readFile($file_path) {
    $file_path = current($file_path);

    if (!$this->fs->exists($file_path)) {
      return NULL;
    }

    switch (pathinfo($file_path, PATHINFO_EXTENSION)) {
      case 'yaml':
      case 'yml':
        return Yaml::parseFile($file_path);

      case 'env':
        return parse_ini_file($file_path);

      default:
        return file_get_contents($file_path);

    }
  }

  /**
   * Write content to a file.
   *
   * @param string $file_path
   *   System path to write to.
   * @param string $contents
   *   File contents to write.
   */
  protected function dumpFile($file_path, $contents) {
    if (str_ends_with($file_path, '.env')) {
      $contents = $this->formatIniData($contents);
    }

    if (str_ends_with($file_path, '.yml') || str_ends_with($file_path, '.yaml')) {
      $contents = Yaml::dump($contents, 6);
    }

    $this->fs->dumpFile($file_path, $contents);
  }

  /**
   * Format data into ini style data.
   *
   * @param array $data
   *   Array of data to be written.
   * @param int $i
   *   Ini file index.
   *
   * @return string
   *   string of ini format data.
   */
  private function formatIniData(array $data, $i = 0) {
    $str = "";
    foreach ($data as $key => $val) {
      if (is_array($val)) {
        $str .= str_repeat(" ", $i * 2) . "[$key]" . PHP_EOL;
        $str .= $this->formatIniData($val, $i + 1);
      }
      else {
        $str .= str_repeat(" ", $i * 2) . "$key = $val" . PHP_EOL;
      }
    }
    return $str;
  }

}
