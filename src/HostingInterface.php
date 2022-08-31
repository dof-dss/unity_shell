<?php

namespace UnityShell;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Interface for hosting services.
 */
interface HostingInterface {

  /**
   * Generates the hosting setup and configuration.
   *
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   Symfony style instance.
   */
  public function build(SymfonyStyle $io);

}
