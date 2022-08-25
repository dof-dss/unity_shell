<?php

namespace UnityShell\Models;

use Exception;
use InvalidArgumentException;
use RomaricDrigon\MetaYaml\Loader\YamlLoader;
use RomaricDrigon\MetaYaml\MetaYaml;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use UnityShell\FileSystemDecorator;

class Project {

  /**
   * The project definition.
   *
   * @var object|string[]|null
   */
  protected array $project;

  /**
   * The FileSystemDecorator.
   *
   * @var \UnityShell\FileSystemDecorator
   */
  private FileSystemDecorator $fs;


  public function __construct() {
    $this->fs = new FileSystemDecorator(new Filesystem());

    if (!$this->fs()->exists('/project/project.yml')) {
      throw new FileNotFoundException("Unity project file not found.");
    }

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

  public function addSite(string $site_id, array $site_data) {
    // @todo Validate site data.
    $this->project['sites'][$site_id] = $site_data;
    $this->save();
  }

  public function updateSite($site_id, $site_data) {
    // @todo Validate site data.
    if (!array_key_exists($site_id, $this->sites())) {
      throw new InvalidArgumentException("Site ID '$site_id' does not exist in the project.");
    }

    $this->project['sites'][$site_id] = $site_data;
    $this->save();
  }

  public function removeSite($site_id) {
    if (!array_key_exists($site_id, $this->sites())) {
      throw new InvalidArgumentException("Site ID '$site_id' does not exist in the project.");
    }
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
   * Sites for the project.
   *
   * @return array
   *   Array of project sites.
   */
  public function sites() {
    return $this->project['sites'];
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
