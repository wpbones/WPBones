<?php

namespace WPKirk\WPBones\Foundation;

use WPKirk\WPBones\Support\ServiceProvider;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * WordPress Schedule Service Provider.
 *
 * This class is a simple way to create scheduled events in WordPress.
 *
 * @package WPKirk\WPBones\Foundation
 *
 * @since 1.8.0
 *
 */
abstract class WordPressScheduleServiceProvider extends ServiceProvider
{

  /**
   * The event hook name.
   *
   * @var string
   *
   */
  protected $hook = '';

  /**
   * The event recurrence schedules.
   * You may use the following values: 'hourly' | 'twicedaily' | 'daily' | 'weekly'
   *
   * {@see '$this->recurrences()'}
   * {@see 'wp_get_schedules'}
   *
   * @var string Usually 'hourly' | 'twicedaily' | 'daily' | 'weekly'
   *
   */
  protected $recurrence = 'daily';

  /**
   * Init the registered shortcodes.
   *
   * @access private
   *
   */
  public function register()
  {
    // you can override this method to set the properties
    $this->boot();

    if (!wp_next_scheduled($this->hook)) {
      wp_schedule_event(time(), $this->recurrence, $this->hook);
    }

    add_action($this->hook, [$this, 'run']);

    // Clear the scheduled event on plugin deactivation
    register_deactivation_hook($this->plugin->file, [$this, '__clear']);
  }

  /**
   * Run the scheduled event.
   *
   */
  abstract public function run();

  /**
   * Clear the scheduled event.
   *
   * @access private Use `clear()`
   */
  public function __clear()
  {
    wp_clear_scheduled_hook($this->hook);

    if (method_exists($this, 'clear')) {
      $this->clear();
    }
  }

  /**
   * You may override this method
   */
  public function boot()
  {
    // You may override this method
  }

  /**
   * Get the event recurrence schedules.
   *
   * That's an alias of wp_get_schedules()`.
   *
   * @see wp_get_schedules()
   *
   * @return array
   */
  public function recurrences()
  {
    return wp_get_schedules();
  }
}
