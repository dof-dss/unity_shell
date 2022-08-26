<?php

namespace UnityShell\Services\Hosting\Lando;

use UnityShell\HostingInterface;

class Lando implements HostingInterface {
  public function build() {
    return "Building Lando hosting";
  }
}