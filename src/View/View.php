<?php

namespace WPKirk\WPBones\View;

if (!defined('ABSPATH')) {
    exit();
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
    protected $adminStyles = [];
    protected $adminScripts = [];

    /**
   * List of the scripts to localize.
   *
   * @var array
   */
  protected $localizeScripts = [];

  /**
     * List of styles and script to enqueue in frontend.
     *
     * @var array
     */
    protected $styles = [];
    protected $scripts = [];

    /**
     * Create a new View.
     *
     * @param mixed  $container Usually a container/plugin.
     * @param string $key       Optional. This is the path of view.
     * @param mixed  $data      Optional. Any data to pass to view.
     */
    public function __construct($container, $key = null, $data = null)
    {
        $this->container = $container;
        $this->key = $key;
        $this->data = $data;
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

        $func = function () {
            // make available Html as facade
            if (!class_exists('WPKirk\Html')) {
                class_alias('\WPKirk\WPBones\Html\Html', 'WPKirk\Html', true);
            }

            // make available plugin instance
            $plugin = $this->container;

            // make available passing params
            if (!is_null($this->data) && is_array($this->data)) {
                foreach ($this->data as $var) {
                    extract($var);
                }
            }

            // include view
            include $this->container->getBasePath() . '/resources/views/' . $this->filename();
        };

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
            foreach ($this->adminScripts as $script) {
        $src = $this->container->js . '/' . $script[0] . '.js';
                wp_enqueue_script($script[0], $src, $script[1], $script[2], true);
      }
    }

    if (!empty($this->localizeScripts)) {
      foreach ($this->localizeScripts as $script) {
        wp_localize_script($script[0], $script[1], $script[2]);
            }
        }
    }

    protected function admin_print_styles()
    {
        if (!empty($this->adminStyles)) {
            foreach ($this->adminStyles as $style) {
                $src = $this->container->css . '/' . $style[0] . '.css';
                wp_enqueue_style($style[0], $src, $style[1], $style[2]);
            }
        }
    }

    protected function wp_enqueue_scripts()
    {
        if (!empty($this->scripts)) {
            foreach ($this->scripts as $script) {
                $src = $this->container->js . '/' . $script[0]  . '.js';
                wp_enqueue_script($script[0], $src, $script[1], $script[2], true);
            }
        }

    if (!empty($this->localizeScripts)) {
      foreach ($this->localizeScripts as $script) {
        wp_localize_script($script[0], $script[1], $script[2]);
      }
    }
    }

    protected function wp_print_styles()
    {
        if (!empty($this->styles)) {
            foreach ($this->styles as $style) {
                $src = $this->container->css . '/' . $style[0]  . '.css';
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
   * Attach a new script to localize.
   *
   * @param string $handle Name of script.
   * @param string $name   Name of the variable.
   * @param array  $data   Data to pass.
   *
   * @return $this
   */
  public function withLocalizeScripts($handle, $name, $data): View
  {
    $this->localizeScripts[] = [$handle, $name, $data];

    return $this;
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
