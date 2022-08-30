<?php

namespace UnityShell;

use Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application as ParentApplication;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use UnityShell\Commands\ProjectBuildCommand;
use UnityShell\Commands\ProjectCreateCommand;
use UnityShell\Commands\ProjectInfoCommand;
use UnityShell\Commands\SiteAddCommand;
use UnityShell\Commands\SiteEditCommand;
use UnityShell\Commands\SiteRemoveCommand;

/**
 * Unity Shell Application.
 */
class Application extends ParentApplication {

  private $container;

  /**
   * Class constructor.
   *
   * @inheritDoc
   */
  public function __construct() {
    parent::__construct("Unity Shell", "2.0.0");

    $this->addCommands([
      new CompletionCommand(),
      new ProjectBuildCommand(),
      new ProjectCreateCommand(),
      new ProjectInfoCommand(),
      new SiteAddCommand(),
      new SiteEditCommand(),
      new SiteRemoveCommand(),
    ]);
  }

  /**
   * @return ContainerBuilder
   */
  public function container()
  {
    if (!isset($this->container)) {
      $this->container = new ContainerBuilder();
      $loader = new YamlFileLoader($this->container, new FileLocator());
      $loader->load(Utils::shellRoot() . '/services.yml');
    }

    return $this->container;
  }

}
