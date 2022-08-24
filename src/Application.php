<?php
namespace UnityShell;

use UnityShell\Command\HelpCommand;
use Symfony\Component\Console\Application as ParentApplication;

class Application extends ParentApplication
{

  public function __construct()
  {
    parent::__construct("Unity Shell", "1.0.0");
    $this->addCommands([
      new HelpCommand()
    ]);
  }

}