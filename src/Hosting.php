<?php

namespace UnityShell;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use UnityShell\Models\Project;
use UnityShell\Services\FileSystemDecorator;

/**
 * Base class for hosting services.
 */
abstract class Hosting {

  /**
   * The service status.
   *
   * @var bool
   */
  protected bool $isEnabled = FALSE;

  /**
   * The Dependency Injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * The project definition.
   *
   * @var \UnityShell\Models\Project
   */
  protected $project;

  /**
   * Symfony Style instance.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  protected $io;

  /**
   * The FileSystemDecorator.
   *
   * @var \UnityShell\Services\FileSystemDecorator
   */
  private FileSystemDecorator $fs;

  /**
   * The service instructions.
   *
   * @var array
   */
  protected array $instructions = [];

  /**
   * Hosting constructor.
   *
   * @throws \Exception
   */
  public function __construct() {
    $this->container = new ContainerBuilder();
    $loader = new YamlFileLoader($this->container, new FileLocator());
    $loader->load(Utils::shellRoot() . '/services.yml');

    $this->fs = $this->container->get('unityshell.filesystem');
    $this->project = new Project();

    // Enable if the hosting service has the required directory in Unity Base.
    $this->isEnabled = $this->fs()->exists('/.hosting/' . $this->name());
  }

  /**
   * Generates the hosting setup and configuration.
   *
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   Symfony style instance.
   */
  public function build(SymfonyStyle $io) {
    $io->section($this->name());
  }

  /**
   * Returns service instructions.
   */
  public function instructions() {
    return $this->instructions;
  }

  /**
   * Add to the service instructions.
   */
  public function addInstructions(string $instruction) {
    $this->instructions[] = $instruction;
  }

  /**
   * The name of the hosting service.
   *
   * @return string
   *   Hosting service name.
   */
  public function name() {
    return (new \ReflectionClass($this))->getShortName();
  }

  /**
   * Returns the DI container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerBuilder
   *   Dependency Injection container.
   */
  protected function container() {
    return $this->getApplication()->container();
  }

  /**
   * Indicates if the hosting service is enabled.
   *
   * @return bool
   *   True for enabled, otherwise false.
   */
  public function isEnabled() {
    return $this->isEnabled;
  }

  /**
   * The Filesystem.
   *
   * @return mixed|object|null
   *   The Filesystem decorator instance.
   */
  protected function fs() {
    return $this->fs;
  }

  /**
   * The project definition.
   *
   * @return \UnityShell\Models\Project
   *   Current project definition.
   */
  protected function project() {
    return $this->project;
  }

  /**
   * Symfony Style.
   *
   * @return \Symfony\Component\Console\Style\SymfonyStyle
   *   The Symfony Style instance.
   */
  protected function io() {
    return $this->io;
  }

}
