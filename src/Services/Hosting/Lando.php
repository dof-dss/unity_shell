<?php

namespace UnityShell\Services\Hosting;

use UnityShell\Hosting;
use UnityShell\HostingInterface;

class Lando extends Hosting implements HostingInterface {
  public function build() {
    return "Building Lando hosting";
  }
}
