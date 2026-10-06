<?php

namespace WPKirk\WPBones\Routing\Pages\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * You can use this class to create a custom page.
 *
 * Create a folder 'pages' in your plugin root directory
 * and create a file named 'page-slug.php'.
 * Then you can use this class to create a custom page.
 *
 * Add a public capability() method that returns the capability a user needs to open the
 * page. Without it, the page asks for `read`: any logged-in user.
 *
 */
abstract class Page
{
  /**
   * The plugin instance.
   */
  protected $plugin;

  public function __construct($plugin)
  {
    $this->plugin = $plugin;
  }

  /**
   * Return the page title.
   *
   * @return string
   */
  abstract public function title();

  /**
   * Render the page
   */
  abstract public function render();
}
