<?php

namespace UnityShell;

use Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand;
use Symfony\Component\Console\Application as ParentApplication;
use UnityShell\Commands\ProjectBuildCommand;
use UnityShell\Commands\ProjectCreateCommand;
use UnityShell\Commands\ProjectInfoCommand;
use UnityShell\Commands\SiteAddCommand;
use UnityShell\Commands\SiteEditCommand;
use UnityShell\Commands\SiteRemoveCommand;
use UnityShell\Commands\TestCommand;

/**
 * Unity Shell Application.
 */
class Application extends ParentApplication {

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

}
