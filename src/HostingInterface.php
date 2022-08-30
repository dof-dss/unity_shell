<?php

namespace UnityShell;

use Symfony\Component\Console\Style\SymfonyStyle;

interface HostingInterface {
  public function build(SymfonyStyle $io);
}
