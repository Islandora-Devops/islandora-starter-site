<?php

namespace Islandora;

use Composer\Script\Event;
use Composer\Util\Platform;

class StarterSite {

  public static function rootPackageInstall(Event $event) {
    $pretty_version = $event->getComposer()->getPackage()->getPrettyVersion();
    var_dump($pretty_version);
  }

}
