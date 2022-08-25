<?php

namespace UnityShell\Models;

use Exception;
use RomaricDrigon\MetaYaml\Loader\YamlLoader;
use RomaricDrigon\MetaYaml\MetaYaml;
use Symfony\Component\Filesystem\Filesystem;
use UnityShell\FileSystemDecorator;

class Project {

  protected $project;

  /**
   * The FileSystemDecorator.
   *
   * @var \UnityShell\FileSystemDecorator
   */
  private FileSystemDecorator $fs;


  public function __construct() {

    $this->fs = new FileSystemDecorator(new Filesystem());

    // Unity2 Project file.
    $project = $this->fs()->readFile('/project/project.yml');

    try {
      $this->validate($project);
      $this->project = $project;
    } catch (Exception $exception) {
      throwException($exception);
    }
  }

  public function save() {
    try {
      $this->validate($this->project);
      $this->fs()->dumpFile('/project/project.yml', $this->project);
    } catch (Exception $exception) {
      throwException($exception);
    }
  }

  public function createSite($site_data) {
    // @todo Validate site data.
    // @todo warn if no project present.
    $this->project['sites'][] = $site_data;
    $this->save();
  }

  public function updateSite($site_id, $site_data) {
    // @todo Validate site data.
    // @todo warn if no project or site present.
    $this->project['sites'][$site_id] = $site_data;
    $this->save();
  }

  public function deleteSite($site_id) {
    // @todo warn if no project or site present.
    unset($this->project['sites'][$site_id]);
    $this->save();
  }

  protected function validate($project_data) {
    // Validate Project file.
    $yaml_loader = new YamlLoader();
    $schema_data = $yaml_loader->loadFromFile(UNITYSH_ROOT . '/resources/schemas/unity_project.yml');
    $schema = new MetaYaml($schema_data);

    try {
      return $schema->validate($project_data);
    } catch (Exception $exception) {
      return $exception;
    }
  }

  /**
   * FileSystemDecorator getter.
   *
   * @return \UnityShell\FileSystemDecorator
   *   The FileSystemDecorator.
   */
  public function fs() {
    return $this->fs;
  }
}
