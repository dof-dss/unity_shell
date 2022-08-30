<?php

namespace UnityShell;

use DrupalFinder\DrupalFinder;

class Utils {

  /**
   * Create a machine safe application ID.
   *
   * @param string $name
   *   Name of the project to create an ID for.
   *
   * @return string
   *   Machine safe application ID.
   */
  public static function createApplicationId($name) {
    return strtolower(str_replace(' ', '_', $name));
  }

  public static function shellRoot() {
    return UNITYSHELL_ROOT;
  }

  public static function projectRoot() {
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    return $drupalFinder->getComposerRoot();
  }

}