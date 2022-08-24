<?php
namespace UnityShell;

use Symfony\Component\Console\Application as ParentApplication;
use UnityShell\Command\ProjectBuildCommand;
use UnityShell\Command\ProjectCreateCommand;
use UnityShell\Command\ProjectInfoCommand;
use UnityShell\Command\SiteAddCommand;
use UnityShell\Command\SiteEditCommand;
use UnityShell\Command\SiteRemoveCommand;

class Application extends ParentApplication
{

  public function __construct()
  {
    parent::__construct("Unity Shell", "2.0.0");

    $this->addCommands([
      new ProjectBuildCommand(),
      new ProjectCreateCommand(),
      new ProjectInfoCommand(),
      new SiteAddCommand(),
      new SiteEditCommand(),
      new SiteRemoveCommand(),
    ]);

  }

}