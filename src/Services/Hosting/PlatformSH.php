<?php

namespace UnityShell\Services\Hosting;

use Symfony\Component\Yaml\Tag\TaggedValue;
use UnityShell\Hosting;
use UnityShell\HostingInterface;
use UnityShell\Utils;

class PlatformSH extends Hosting implements HostingInterface {

  public function build() {
    $routes = [];
    // Flag to determine if we need to include Solr configuration
    // in the Platform services file.
    $solr_required = FALSE;

    $platform = $this->fs()->readFile('/.hosting/PlatformSH/templates/.platform.app.template.yaml');
    $services = $this->fs()->readFile('/.hosting/PlatformSH/templates/.services.template.yaml');

    $platform['name'] = Utils::createApplicationId($this->project()->name());

    foreach ($this->project()->sites() as $site_id => $site) {

      // Create database relationship.
      if (!empty($site['database'])) {
        $platform['relationships'][$site_id] = 'db:' . $site['database'];
      }

      // Create solr relationship.
      if (!empty($site['solr'])) {
        $platform['relationships'][$site_id . '_solr'] = 'solr:' . $site['solr'];
        $solr_required = TRUE;
      }

      // Create Platform SH services.
      $services['db']['configuration']['schemas'][] = $site_id . 'db';
      $services['db']['configuration']['endpoints'][$site_id] = [
        'default_schema' => $site_id . 'db',
        'privileges' => [
          $site_id . 'db' => 'admin',
        ],
      ];

      if (!empty($site['solr'])) {
        $solr_conf_dir = new TaggedValue('archive', 'solr_config/');
        $services['solr']['configuration']['cores'][$site_id . '_index'] = [
          'conf_dir' => $solr_conf_dir,
        ];

        $services['solr']['configuration']['endpoints'][$site_id] = [
          'core' => $site_id . '_index',
        ];
        $solr_required = TRUE;
      }

      // Create cron entries.
      if (!empty($site['cron_spec']) && !empty($site['cron_cmd'])) {
        $platform['crons'][$site_id]['spec'] = $site['cron_spec'];
        $platform['crons'][$site_id]['cmd'] = $site['cron_cmd'];
      }

      // Create development instance route.
      if ($site['status'] !== 'production') {
        // Create Platform SH route.
        $routes['https://www.' . $site['url'] . '/'] = [
          'type' => 'upstream',
          'upstream' => $platform['name'] . ':http',
          'cache' => [
            'enabled' => 'false',
          ],
        ];

        $routes['https://' . $site['url'] . '/'] = [
          'type' => 'redirect',
          'to' => 'https://www.' . $site['url'] . '/',
        ];
      }
    }

    // Update platform post deploy hook with list of sites.
    $platform['hooks']['post_deploy'] = str_replace('<sites_placeholder>', implode(' ', array_keys($this->project()->sites())), $platform['hooks']['post_deploy']);

    // Add 'Catch all' to PlatformSH routing.
    $routes['https://www.{all}/'] = [
      'type' => 'upstream',
      'upstream' => $platform['name'] . ':http',
      'cache' => [
        'enabled' => 'false',
      ],
    ];

    $routes['https://{all}/'] = [
      'type' => 'redirect',
      'to' => 'https://www.{all}/',
    ];

    // Remove Solr config if none of the sites use Solr.
    if (!$solr_required) {
      unset($services['solr']);
    }

    // Write Platform configuration files.
    $this->fs()->dumpFile('/.platform.app.yaml', $platform);
    $this->fs()->dumpFile('/.platform/services.yaml', $services);
    $this->fs()->dumpFile('/.platform/routes.yaml', $routes);

    // Copy Solr configuration to platform directory.
    $this->fs()->mirror('/.hosting/platformsh/resources/solr_config', '/.platform/solr_config');
  }

}
