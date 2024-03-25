<?php

namespace WPKirk\WPBones\View;

use eftec\bladeone\BladeOne;

if (!defined('ABSPATH')) {
  exit;
}

class View
{

  /**
   * A plugin instance container.
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

  protected BladeOne $blade;

  /**
   * Create a new View.
   *
   * @param mixed       $container Usually a container/plugin.
   * @param string|null $key       Optional. This is the path of view.
   * @param array       $data      Optional. Any data to pass to view.
   */
  public function __construct($container, string $key = null, array $data = [])
  {
    $this->container = $container;
    $this->key       = $key;
    $this->data      = $data;

	$cache = $this->container->getBasePath() . '/.cache';
	if (!file_exists($cache)) {
		mkdir($cache, 0777, true);
	}

	//Initiate BladeOne
	$this->blade = new BladeOne($this->container->getBasePath() . '/resources/views', $cache, BladeOne::MODE_AUTO);
  }

  /**
   * Get the string rappresentation of a view.
   *
   * @return string
   */
  public function __toString(): string
  {
    return (string) $this->render();
  }

  /**
   * Get the view content.
   *
   * @param bool $asHTML Set to TRUE to get the content of view as string/html.
   * @return mixed
   */
  public function render($asHTML = false)
  {
    if (!$this->container->isAjax()) {
      $this->admin_enqueue_scripts();
      $this->admin_print_styles();
      $this->wp_enqueue_scripts();
      $this->wp_print_styles();
    }

	// Check if view exists as a blade file
	if (file_exists($this->container->getBasePath() . '/resources/views/' . $this->filename()) && strpos($this->filename(), '.blade.php') !== false) {
		// Inject the plugin instance into the view
		$this->data['plugin'] = $this->container;

		// Render the blade file
		$func = function () {
			echo $this->blade->run($this->filename(), $this->data);
		};
	} else {
		// Keep the old way of rendering views for backward compatibility
		$func = function() {
			// make available Html as facade
			if(!class_exists('WPKirk\Html')) {
				class_alias('\WPKirk\WPBones\Html\Html', 'WPKirk\Html', true);
			}

			// make available plugin instance
			$plugin = $this->container;

			// make available passing params
			if(!is_null($this->data) && is_array($this->data)) {
				foreach($this->data as $var) {
					extract($var);
				}
			}

			// include view
			include $this->container->getBasePath() . '/resources/views/' . $this->filename();
		};
	}

    if ($this->container->isAjax() || $asHTML) {
      ob_start();
      $func();
      $content = ob_get_contents();
      ob_end_clean();

      return $content;
    }

    return $func();
  }

  protected function admin_enqueue_scripts()
  {
    if (!empty($this->adminScripts)) {
      $minified = $this->container->config('plugin.minified', false) ? ".min" : '';
      foreach ($this->adminScripts as $script) {
        $src = $this->container->js . '/' . $script[0] . $minified . '.js';
        wp_enqueue_script($script[0], $src, $script[1], $script[2], true);
      }
    }
  }

  protected function admin_print_styles()
  {
    if (!empty($this->adminStyles)) {
      $minified = $this->container->config('plugin.minified', false) ? ".min" : '';
      foreach ($this->adminStyles as $style) {
        $src = $this->container->css . '/' . $style[0] . $minified . '.css';
        wp_enqueue_style($style[0], $src, $style[1], $style[2]);
      }
    }
  }

  protected function wp_enqueue_scripts()
  {
    if (!empty($this->scripts)) {
      $minified = $this->container->config('plugin.minified', false) ? ".min" : '';
      foreach ($this->scripts as $script) {
        $src = $this->container->js . '/' . $script[0] . $minified . '.js';
        wp_enqueue_script($script[0], $src, $script[1], $script[2], true);
      }
    }
  }

  protected function wp_print_styles()
  {
    if (!empty($this->styles)) {
      $minified = $this->container->config('plugin.minified', false) ? ".min" : '';
      foreach ($this->styles as $style) {
        $src = $this->container->css . '/' . $style[0] . $minified . '.css';
        wp_enqueue_style($style[0], $src, $style[1], $style[2]);
      }
    }
  }

  /**
   * Get the filename.
   *
   * @return string
   */
  protected function filename(): string
  {
	  $exts = ['.blade.php', '.php'];
	  foreach ($exts as $ext) {
		  if (file_exists($this->container->getBasePath() . '/resources/views/' . str_replace('.', '/', $this->key) . $ext)) {
			  return str_replace('.', '/', $this->key) . $ext;
		  }
	  }

	  // If no file is found, return the first extension
	  return str_replace('.', '/', $this->key) . '.php';
  }

  /**
   * Return the content of view.
   *
   * @return string
   */
  public function toHTML(): string
  {
    ob_start();

    $this->render();

    $content = ob_get_contents();
    ob_end_clean();

    return $content;
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
  public function withAdminStyles($name, $deps = [], $ver = []): View
  {
    $this->adminStyles[] = [$name, $deps, $ver];

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
  public function withAdminScripts($name, $deps = [], $ver = []): View
  {
    $this->adminScripts[] = [$name, $deps, $ver];

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
  public function withStyles($name, $deps = [], $ver = []): View
  {
    $this->styles[] = [$name, $deps, $ver];

    return $this;
  }

  /**
   * Load a new css resource in front-end.
   *
   * @param string $name Name of script.
   * @param array  $deps Optional. Array of slug deps
   * @param array  $ver  Optional. Version.
   *
   * @return $this
   */
  public function withScripts($name, $deps = [], $ver = []): View
  {
    $this->scripts[] = [$name, $deps, $ver];

    return $this;
  }

  /**
   * Data to pass to the view.
   *
   * @param mixed $data Array or single data.
   *
   * @return $this
   * @example     $instance->with( [ 'foo' => 'bar' ] )
   *
   * @example     $instance->with( 'foo', 'bar' )
   */
  public function with($data): View
  {
    if (is_array($data)) {
      $this->data[] = $data;
    } elseif (func_num_args() > 1) {
      $this->data[] = [$data => func_get_arg(1)];
    }

    return $this;
  }
}
