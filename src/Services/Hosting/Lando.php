<?php

namespace UnityShell\Services\Hosting;

use UnityShell\Hosting;
use UnityShell\HostingInterface;
use UnityShell\Utils;

class Lando extends Hosting implements HostingInterface {

  public function build($io) {
    parent::build($io);
    $data = [];

    $data['name'] = Utils::createApplicationId($this->project()->name());

    foreach ($this->project()->sites() as $site_id => $site) {

      // Create Lando proxy.
      $data['proxy']['appserver'][] = $site['url'] . '.lndo.site';

      // Create solr relationship.
      if (!empty($site['solr'])) {
        $data['services'][$site_id . '_solr'] = [
          'type' => 'solr:7',
          'portforward' => TRUE,
          'core' => 'default',
          'config' => [
            'dir' => '.lando/config/solr/7.x/default',
          ],
        ];
      }
    }

    // Copy the base Unity configuration for Lando.
    $io->writeln("Creating Lando base configuration file.");
    $this->fs()->copy('/.hosting/Lando/templates/.lando.base.yml', '/.lando.base.yml');

    // Create project specific Lando file.
    $io->writeln("Creating Lando project configuration file.");
    $this->fs()->dumpFile('/.lando.yml', $data);

    // Copy Lando resources to the project.
    $io->writeln("Copying Lando resources to project.");
    $this->fs()->mkdir('/.lando');
    $this->fs()->mirror('/.hosting/Lando/resources/', '/.lando');

    // Copy Lando Drupal services file if one doesn't already exist.
    if (!$this->fs()->exists('/web/sites/default/services.yml')) {
      $io->writeln("Copying Lando Drupal services file.");
      $this->fs()->copy('/.hosting/Lando/templates/drupal.services.yml', '/web/sites/default/services.yml');
    }

    // Copy Lando Redis config file if one doesn't already exist.
    if (!$this->fs()->exists('/web/sites/default/redis.services.yml')) {
      $io->writeln("Copying Lando Redis config file.");
      $this->fs()->copy('/.hosting/Lando/templates/redis.services.yml', '/web/sites/default/redis.services.yml');
    }

    // Create public files directory if one doesn't already exist.
    if (!$this->fs()->exists('/web/files')) {
      $io->writeln("Creating Drupal public files directory.");
      $this->fs()->mkdir('/web/files');
    }

    // Create private files directory if one doesn't already exist.
    if (!$this->fs()->exists('/.lando/private')) {
      $io->writeln("Creating Drupal private files directory.");
      $this->fs()->mkdir('/.lando/private');
    }

    // Check for an .env file and copy example if missing.
    if (!$this->fs()->exists('/.env')) {
      // Copy from the sample env file as it may have project specific entries.
      // If sample.en doesn't exist, copy the basic version.
      if (!$this->fs()->exists('/.env.sample')) {
        $this->fs()->copy('/.hosting/Lando/templates/.env.sample', '/.env.sample');
      }

      $this->fs()->copy('/.env.sample', '/.env');
      $io->success('Created local .env file');
    }

    // Read .env file to check for some default Drupal environment settings.
    $env_data = $this->fs()->readFile('/.env');

    if (empty($env_data['HASH_SALT'])) {
      if ($io->confirm('Hash Salt was not found in the .env file. Would you like to add one?')) {
        $env_data['HASH_SALT'] = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(random_bytes(55)));
        $this->fs()->dumpFile('/.env', $env_data);
        $io->success('Creating local site hash within .env file');
      }
    }
  }

}
