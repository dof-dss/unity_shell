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
  }

}
