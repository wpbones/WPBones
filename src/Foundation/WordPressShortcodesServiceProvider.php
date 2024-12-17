<?php

namespace WPKirk\WPBones\Foundation;

use WPKirk\WPBones\Support\ServiceProvider;

if (!defined('ABSPATH')) {
  exit();
}

abstract class WordPressShortcodesServiceProvider extends ServiceProvider
{
  /**
   * List of registered shortcodes. Here you will use a methods list.
   *
   * @var array
   */
  protected $shortcodes = [];

  /**
   * Init the registered shortcodes.
   *
   * @access private
   */
  public function register()
  {
    // you can override this method to set the properties
    $this->boot();

    foreach ($this->shortcodes as $shortcode => $method) {
      add_shortcode($shortcode, [$this, $method]);
    }
  }

  /**
   * You may override this method in order to register your own actions and filters.
   *
   */
  public function boot()
  {
    // You may override this method
  }
}
