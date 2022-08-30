<?php

namespace UnityShell;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use UnityShell\Models\Project;
use UnityShell\Services\FileSystemDecorator;

abstract class Hosting {

  protected bool $isEnabled = FALSE;

  protected $container;

  protected $provider;

  protected $project;

  /**
   * The FileSystemDecorator.
   *
   * @var \UnityShell\Services\FileSystemDecorator
   */
  private FileSystemDecorator $fs;

  public function __construct() {
    $this->container = new ContainerBuilder();
    $loader = new YamlFileLoader($this->container, new FileLocator());
    $loader->load(UNITYSHELL_ROOT . '/services.yml');

    $this->fs = $this->container->get('unityshell.filesystem');
    $this->provider = (new \ReflectionClass($this))->getShortName();

    $this->project = new Project();

    $this->isEnabled = $this->fs()->exists('/.hosting/' . $this->provider);
  }

  protected function container() {
    return $this->getApplication()->container();
  }

  public function isEnabled() {
    return $this->isEnabled;
  }

  protected function fs() {
    return $this->fs;
  }

  protected function project() {
    return $this->project;
  }

}