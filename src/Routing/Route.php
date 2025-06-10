<?php

namespace Ondapresswp\WPBones\Routing;

if (!defined('ABSPATH')) {
  exit();
}

class Route
{
  /**
   * Custom page routes list.
   *
   * @var array
   */
  public static array $menu = [];

  /**
   * Set the right route path.
   *
   * @param string $path
   */
  public static function get(string $path): void
  {
    self::$menu[] = $path;
  }
}
