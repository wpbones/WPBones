<?php

namespace WPKirk\WPBones\Support;

if (!defined('ABSPATH')) {
    exit;
}

abstract class Widget extends \WP_Widget
{

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
        $args = wp_parse_args($args);

        echo $args['before_widget'];

        $this->viewWidget($args, $instance)->render();

        echo $args['after_widget'];
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
