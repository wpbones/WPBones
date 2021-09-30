<?php

namespace WPKirk\WPBones\Support;

if (!defined('ABSPATH')) {
  exit;
}

abstract class Widget extends \WP_Widget
{

  /**
   * Base ID for the widget, lower case, if left empty a portion of the widget's class name will be used. Has to be
   * unique.
   *
   * @var string
   */
  public $id_base = '';

  /**
   * Name for the widget displayed on the configuration page.
   *
   * @var string
   */
  public $name = '';

  /**
   * Optional. Passed to wp_register_sidebar_widget()
   *
   * - description: shown on the configuration page
   * - classname
   *
   * @var array
   */
  public $widget_options = [];

  /**
   * Optional. Passed to wp_register_widget_control()
   *
   * - width: required if more than 250px
   * - height: currently not used but may be needed in the future
   *
   * @var array
   */
  public $control_options = [];

  /**
   * An instance of plugin.
   *
   * @var
   */
  protected $plugin;

  /**
   * Widget constructor.
   *
   * @param string $plugin
   */
  public function __construct($plugin)
  {
    $this->plugin = $plugin;

    parent::__construct($this->id_base, $this->name, $this->widget_options, $this->control_options);
  }

  /**
   * Return a key pairs array with the default value for widget.
   *
   * @return array
   */
  abstract public function defaults();

  /**
   * Echo the widget content.
   * Subclasses should over-ride this function to generate their widget code.
   *
   * @param array $args     Display arguments including before_title, after_title, before_widget, and after_widget.
   * @param array $instance The settings for the particular instance of the widget
   */
  public function widget($args, $instance)
  {
    /**
     * @var $before_widget
     * @var $after_widget
     */
    extract($args);

    echo $before_widget;

    $this->viewWidget($args, $instance)->render();

    echo $after_widget;
  }

  /**
   * Return the view resource path. eg: "widget.demo".
   *
   * @param array $args     Display arguments including before_title, after_title, before_widget, and after_widget.
   * @param array $instance The settings for the particular instance of the widget
   *
   */
  abstract public function viewWidget($args, $instance);

  /**
   * Echo the settings update form
   *
   * @param array $instance Current settings
   *
   * @return void
   */
  public function form($instance)
  {
    echo $this->viewForm($instance);
  }

  /**
   * Return the view resource path. eg: "widget.form".
   *
   * @param $instance
   * @return string
   */
  abstract public function viewForm($instance);

}