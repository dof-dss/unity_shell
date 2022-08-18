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
   */
  protected const SITE_STATUS = [
    'development',
    'production',
  ];

  /**
   * Banner to inform the user that an update is available.
   */
  private string $update_banner = <<<BANNER
 /\ /\ _ __   __| | __ _| |_ ___    /_\__   ____ _(_) | __ _| |__ | | ___  / \
/ / \ \ '_ \ / _` |/ _` | __/ _ \  //_\\ \ / / _` | | |/ _` | '_ \| |/ _ \/  /
\ \_/ / |_) | (_| | (_| | ||  __/ /  _  \ V / (_| | | | (_| | |_) | |  __/\_/ 
 \___/| .__/ \__,_|\__,_|\__\___| \_/ \_/\_/ \__,_|_|_|\__,_|_.__/|_|\___\/   
      |_|                                                                     
BANNER;


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

    // @todo Check current version against latest release and cache the response.
    $version = \Composer\InstalledVersions::getVersion('symfony/console');

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
