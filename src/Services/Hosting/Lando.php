<?php

namespace UnityShell\Services\Hosting;

use UnityShell\HostingInterface;

class Lando implements HostingInterface {
  public function build() {
    return "Building Lando hosting";
  }
}