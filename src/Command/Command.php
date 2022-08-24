<?php

namespace UnityShell\Command;

use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use UnityShell\FileSystemDecorator;

abstract class Command extends ConsoleCommand {

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
   * The FileSystemDecorator.
   *
   * @var FileSystemDecorator
   */
  private FileSystemDecorator $fs;

  /**
   * @inheritdoc
   */
  protected function initialize(InputInterface $input, OutputInterface $output)
  {
    \Composer\Command\Command::
    // @todo Create fs as a service and inject.
    $this->fs = new FileSystemDecorator(new Filesystem());
  }

  /**
   * FileSystemDecorator getter.
   *
   * @return FileSystemDecorator
   *   The FileSystemDecorator.
   */
  public function fs() {
    return $this->fs;
  }
}