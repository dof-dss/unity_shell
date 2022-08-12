<?php

namespace App;

use App\Command\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
  name: 'unity:shell',
  description: 'Base command for Unity Shell',
  hidden: TRUE,
)]
/**
 * Base command class for Unity Shell commands.
 */
class UnityShellCommand extends Command {

  /**
   * The Command name.
   *
   * @var string
   */
  protected static $defaultName = 'unity:shell';

  /**
   * The site development status.
   *
   * @var array|string[]
   */
  protected array $site_status = [
    'development',
    'production'
  ];

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
   * UnityShellCommand constructor.
   *
   * @param string|null $name
   *   The application name.
   */
  public function __construct(string $name = NULL) {
    parent::__construct($name);

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
