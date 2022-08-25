<?php

namespace UnityShell\Services\Hosting;

use UnityShell\HostingInterface;

class PlatformSH implements HostingInterface {
  public function build() {
    return "Building PlatformSH hosting";
  }
}