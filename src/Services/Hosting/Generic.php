<?php

namespace UnityShell\Services\Hosting;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use UnityShell\Hosting;
use UnityShell\HostingInterface;

class Generic extends Hosting implements HostingInterface {

  public function build($io) {
    parent::build($io);

    $io->text('Verifying setup for ' . count($this->project()->sites()) . ' site(s).');
    foreach ($this->project()->sites() as $site_id => $site) {
      // If a site folder doesn't exist under project/sites, create it and
      // provide a settings file.
      if (!$this->fs()->exists('/project/sites/' . $site_id)) {
        $io->text('Creating a site directory for ' . $site_id . ' under project/sites/');
        $this->fs()->mkdir('/project/sites/' . $site_id);
        $this->fs()->copy('/.hosting/Generic/resources/multisite.settings.php', '/project/sites/' . $site_id . '/settings.php');
      }

      // Enable our multisite entry by linking from the sites directory to
      // the project directory.
      try {
        $this->fs()->symlink('//app/project/sites/' . $site_id, '/web/sites/' . $site_id);
      }
      catch (IOExceptionInterface $exception) {
        $io->error("An error occurred while linking $site_id site directory: " . $exception->getMessage());
      }

      // If a site config doesn't exist under project/config, create it.
      if (!$this->fs()->exists('/project/config/' . $site_id)) {
        $io->text('Creating config directory for ' . $site_id . ' under project/config/');
        $this->fs()->mkdir('/project/config/' . $site_id);
        $this->fs()->dumpFile('/project/config/' . $site_id . '/.gitkeep', "");

        // Create the default config directories if they don't already exist.
        foreach (['config', 'hosted', 'local', 'production'] as $directory) {
          $io->text('Creating default config directories');
          if (!$this->fs()->exists('/project/config/' . $site_id . '/' . $directory)) {
            $this->fs()->mkdir('/project/config/' . $site_id . '/' . $directory);
          }
        }
      }
    }

    // Copy base Drupal services file is one doesn't already exist.
    if (!$this->fs()->exists('/web/sites/services.yml')) {
      $io->text('Creating Drupal services file from defaults.');
      $this->fs()->copy('/.hosting/Generic/resources/default.services.yml', '/web/sites/services.yml');
    }
  }

}
