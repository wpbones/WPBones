<?php

namespace WPKirk\WPBones\View;

use WPKirk\WPBones\Container\Container;

if ( ! defined( 'ABSPATH' ) ) exit;

class View
{

  /**
   * A plugin instance container.
   *
   * @var $container
   */
  protected $container;
  protected $key;
  protected $data;

  /**
   * List of styles and script to enqueue in admin area.
   *
   * @var array
   */
  protected $adminStyles  = [];
  protected $adminScripts = [];

  /**
   * List of styles and script to enqueue in frontend.
   *
   * @var array
   */
  protected $styles  = [];
  protected $scripts = [];

  /**
   * Create a new View.
   *
   * @param mixed $container Usually a container/plugin.
   * @param null  $key       Optional. This is the path of view.
   * @param null  $data      Optional. Any data to pass to view.
   */
  public function __construct( $container, $key = null, $data = null )
  {
    $this->container = $container;
    $this->key       = $key;
    $this->data      = $data;
  }

  /**
   * Get the filename.
   *
   * @return string
   */
  protected function filename()
  {
    $filename = str_replace( '.', '/', $this->key ) . '.php';

    return $filename;
  }

  protected function admin_print_styles()
  {
    if ( ! empty( $this->adminStyles ) ) {
      $minified = $this->container->config( 'plugin.minified', false ) ? ".min" : '';
      foreach ( $this->adminStyles as $style ) {
        $src = $this->container->getCssAttribute() . '/' . $style[ 0 ] . $minified . '.css';
        wp_enqueue_style( $style[ 0 ], $src, $style[ 1 ], $style[ 2 ] );
      }
    }
  }

  protected function admin_enqueue_scripts()
  {
    if ( ! empty( $this->adminScripts ) ) {
      $minified = $this->container->config( 'plugin.minified', false ) ? ".min" : '';
      foreach ( $this->adminScripts as $script ) {
        $src = $this->container->getJsAttribute() . '/' . $script[ 0 ] . $minified . '.js';
        wp_enqueue_script( $script[ 0 ], $src, $script[ 1 ], $script[ 2 ], true );
      }
    }
  }

  protected function wp_print_styles()
  {
    if ( ! empty( $this->styles ) ) {
      $minified = $this->container->config( 'plugin.minified', false ) ? ".min" : '';
      foreach ( $this->styles as $style ) {
        $src = $this->container->getCssAttribute() . '/' . $style[ 0 ] . $minified . '.css';
        wp_enqueue_style( $style[ 0 ], $src, $style[ 1 ], $style[ 2 ] );
      }
    }
  }

  protected function wp_enqueue_scripts()
  {
    if ( ! empty( $this->scripts ) ) {
      $minified = $this->container->config( 'plugin.minified', false ) ? ".min" : '';
      foreach ( $this->scripts as $script ) {
        $src = $this->container->getJsAttribute() . '/' . $script[ 0 ] . $minified . '.js';
        wp_enqueue_script( $script[ 0 ], $src, $script[ 1 ], $script[ 2 ], true );
      }
    }
  }

  /**
   * Get the string rappresentation of a view.
   *
   * @return string
   */
  public function __toString()
  {
    return (string) $this->render();
  }

  public function render()
  {

    if ( ! $this->container->isAjax() ) {
      $this->admin_enqueue_scripts();
      $this->admin_print_styles();
      $this->wp_enqueue_scripts();
      $this->wp_print_styles();
    }


    $func = function () {

      // make available Html as facade
      if ( ! class_exists( 'WPKirk\Html' ) ) {
        class_alias( '\WPKirk\WPBones\Html\Html', 'WPKirk\Html', true );
      }

      // make available plugin instance
      $plugin = $this->container;

      // make available passing params
      if ( ! is_null( $this->data ) && is_array( $this->data ) ) {
        foreach ( $this->data as $var ) {
          extract( $var );
        }
      }

      // include view
      include $this->container->getBasePath() . '/resources/views/' . $this->filename();
    };

    if ( $this->container->isAjax() ) {
      ob_start();
      $func();
      $content = ob_get_contents();
      ob_end_clean();

      return $content;
    }

    return $func();
  }

  /**
   * Load a new css resource in admin area.
   *
   * @param string $name Name of style.
   * @param array  $deps Optional. Array of slug deps
   * @param array  $ver  Optional. Version.
   *
   * @return $this
   */
  public function withAdminStyles( $name, $deps = [], $ver = [] )
  {
    $this->adminStyles[] = [ $name, $deps, $ver ];

    return $this;
  }

  /**
   * Load a new css resource in admin area.
   *
   * @param string $name Name of script.
   * @param array  $deps Optional. Array of slug deps
   * @param array  $ver  Optional. Version.
   *
   * @return $this
   */
  public function withAdminScripts( $name, $deps = [], $ver = [] )
  {
    $this->adminScripts[] = [ $name, $deps, $ver ];

    return $this;
  }

  /**
   * Load a new css resource in frontend.
   *
   * @param string $name Name of style.
   * @param array  $deps Optional. Array of slug deps
   * @param array  $ver  Optional. Version.
   *
   * @return $this
   */
  public function withStyles( $name, $deps = [], $ver = [] )
  {
    $this->styles[] = [ $name, $deps, $ver ];

    return $this;
  }

  /**
   * Load a new css resource in fonrend.
   *
   * @param string $name Name of script.
   * @param array  $deps Optional. Array of slug deps
   * @param array  $ver  Optional. Version.
   *
   * @return $this
   */
  public function withScripts( $name, $deps = [], $ver = [] )
  {
    $this->scripts[] = [ $name, $deps, $ver ];

    return $this;
  }

  /**
   * Data to pass to the view.
   *
   * @param mixed $data Array or single data.
   *
   * @example     $instance->with( 'foo', 'bar' )
   * @example     $instance->with( [ 'foo' => 'bar' ] )
   *
   * @return $this
   */
  public function with( $data )
  {
    if ( is_array( $data ) ) {
      $this->data[] = $data;
    }
    elseif ( func_num_args() > 1 ) {
      $this->data[] = [ $data => func_get_arg( 1 ) ];
    }

    return $this;
  }

}