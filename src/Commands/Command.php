<?php

namespace UnityShell\Commands;

use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use UnityShell\Models\Project;
use UnityShell\Services\FileSystemDecorator;

/**
 * Base class form building Unity Shell commands.
 */
abstract class Command extends ConsoleCommand {

  /**
   * Command return values.
   */
  public const SUCCESS = 0;
  public const FAILURE = 1;
  public const INVALID = 2;

  /**
   * The site development status.
   */
  protected const SITE_STATUS = [
    'development',
    'production',
  ];

  /**
   * The Unity project definition.
   */
  protected Project $project;

  /**
   * The FileSystemDecorator.
   *
   * @var \UnityShell\Services\FileSystemDecorator
   */
  private FileSystemDecorator $fs;

  /**
   * Initialize common configuration for all Unity Shell commands.
   *
   * @inheritdoc
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    if ($this->getName() !== 'project:create') {
      $this->project = new Project();
    }
  }

  /**
   * Project getter.
   *
   * @return \UnityShell\Models\Project
   *   Current Unity site project definition.
   */
  public function project() {
    return $this->project;
  }

  /**
   * FileSystemDecorator getter.
   *
   * @return \UnityShell\Services\FileSystemDecorator
   *   The FileSystemDecorator.
   */
  public function fs() {
    if (empty($this->fs)) {
      $this->fs = $this->container()->get('unityshell.filesystem');
    }
    return $this->fs;
  }

  protected function container() {
    return $this->getApplication()->container();
  }



}
