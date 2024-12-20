<?php

namespace WPKirk\WPBones\View;

use eftec\bladeone\BladeOne;

if (!defined('ABSPATH')) {
  exit();
}

class View
{
  /**
   * A plugin instance container.
   */
  protected $container;
  protected $data;
  protected $key;

  /**
   * List of styles and script to enqueue in admin area.
   *
   * @var array
   */
  protected $adminAppsModules = [];
  protected $adminAppsScripts = [];
  protected $adminStyles = [];
  protected $adminScripts = [];

  /**
   * List of the scripts to localize.
   *
   * @var array
   */
  protected $localizeScripts = [];

  /**
   * List of the inline scripts.
   *
   * @var array
   */
  protected $inlineScripts = [];

  /**
   * List of the inline styles.
   *
   * @var array
   */
  protected $inlineStyles = [];

  /**
   * List of styles and script to enqueue in theme.
   *
   * @var array
   */
  protected $styles = [];
  protected $scripts = [];

  /**
   * BladeOne instance.
   *
   * @var BladeOne
   */
  protected BladeOne $blade;

  /**
   * Create a new View.
   *
   * @param mixed  $container Usually a container/plugin.
   * @param string $key       Optional. This is the path of view.
   * @param mixed  $data      Optional. Any data to pass to view.
   */
  public function __construct($container, string $key = null, array $data = [])
  {
    $this->container = $container;
    $this->key = $key;
    $this->data = $data;

    $cache = $this->container->basePath . '/.cache';

    if (!file_exists($cache)) {
      mkdir($cache, 0777, true);
    }

    // Initiate BladeOne
    $this->blade = new BladeOne($this->container->basePath . '/resources/views', $cache, BladeOne::MODE_AUTO);
  }

  /**
   * Get the string of a view.
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
   * @param bool $asHTML  Optional. Set to TRUE to get the content of view as string/html.
   * @return mixed
   */
  public function render($asHTML = false)
  {
    if (!$this->container->isAjax()) {
      if (is_admin()) {
        $this->admin_enqueue_scripts();
        $this->admin_print_styles();
      } else {
        $this->wp_enqueue_scripts();
        $this->wp_print_styles();
      }
    }

    // Check if view exists as a blade file
    if (
      file_exists($this->container->basePath . '/resources/views/' . $this->filename()) &&
      strpos($this->filename(), '.blade.php') !== false
    ) {
      // Inject the plugin instance into the view
      $this->data['plugin'] = $this->container;

      // Render the blade file
      $func = function () {
        echo $this->blade->run($this->filename(), $this->data);
      };
    }
    // Use a php file
    else {
      // Check if view exists as a php file

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
        include $this->container->basePath . '/resources/views/' . $this->filename();
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
   * Adds a new Localizes a script.
   *
   * @param string $handle Script handle the data will be attached to.
   * @param string $name   Name for the JavaScript object
   * @param array  $l10n   The data itself. The data can be either a single or multi-dimensional array.
   *
   * @return $this
   */
  public function withLocalizeScript($handle, $name, $l10n): View
  {
    $this->localizeScripts[] = [$handle, $name, $l10n];

    return $this;
  }

  public function withLocalizeScripts($handle, $name, $l10n): View
  {
    _deprecated_function(__METHOD__, '1.6.0', 'withLocalizeScript()');

    return $this->withLocalizeScript($handle, $name, $l10n);
  }

  /**
   * Adds extra code to a registered script.
   *
   * @param string $name      Name of the script will be attached to.
   * @param string $data      String containing the JavaScript to be added.
   * @param string $position  Optional. Whether to add the inline script before the handle
   *                          or after. Default 'after'.
   *
   * @return $this
   */
  public function withInlineScript($name, $data, $position = 'after'): View
  {
    $this->inlineScripts[] = [$name, $data, $position];

    return $this;
  }

  /**
   * Adds extra style to a registered style.
   *
   * @param string $name      Name of the script will be attached to.
   * @param string $data      String containing the JavaScript to be added.
   *
   * @return $this
   */
  public function withInlineStyle($name, $data): View
  {
    $this->inlineStyles[] = [$name, $data];

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
  public function withAdminStyle($name, $deps = [], $ver = []): View
  {
    $this->adminStyles[] = [$name, $deps, $ver];

    return $this;
  }

  /**
   * Load a new css resource in admin area.
   *
   * @param string $name Name of style.
   * @param array  $deps Optional. Array of slug deps
   * @param array  $ver  Optional. Version.
   *
   * @deprecated 1.6.0
   *
   * @return $this
   */
  public function withAdminStyles($name, $deps = [], $ver = []): View
  {
    _deprecated_function(__METHOD__, '1.6.0', 'withAdminStyle()');

    return $this->withAdminStyle($name, $deps, $ver);
  }

  /**
   * Load a new Javascript resource in admin area.
   *
   * @param string $name Name of script.
   * @param array  $deps Optional. Array of slug deps
   * @param array  $ver  Optional. Version.
   *
   * @return $this
   */
  public function withAdminScript($name, $deps = [], $ver = []): View
  {
    $this->adminScripts[] = [$name, $deps, $ver];

    return $this;
  }

  /**
   * Load a new Javascript resource in admin area.
   *
   * @param string $name Name of script.
   * @param array  $deps Optional. Array of slug deps
   * @param array  $ver  Optional. Version.
   *
   * @deprecated 1.6.0
   *
   * @return $this
   */
  public function withAdminScripts($name, $deps = [], $ver = []): View
  {
    _deprecated_function(__METHOD__, '1.6.0', 'withAdminScript()');

    return $this->withAdminScript($name, $deps, $ver);
  }

  /**
   * Load new Javascript (React bundle) resource in admin area.
   *
   * @param string $name      Script handle the data will be attached to.
   * @param bool   $module    Optional. There is a module css.
   * @param string $variable  Optional. Name for the JavaScript object
   * @param array  $data      Optional. The data itself. The data can be either a single or multi-dimensional array.
   */
  public function withAdminAppsScript($name, $module = true, $variable = '', $data = []): View
  {
    ['dependencies' => $deps, 'version' => $ver] = @include $this->container->basePath .
      '/public/apps/' .
      $name .
      '.asset.php';

    $ver = $ver ?: '';

    $this->adminAppsScripts[] = [$name, ['wp-element'], $ver || ''];

    if ($module) {
      $this->adminAppsModules[] = [$name, [], $ver];
    }

    if ($variable) {
      $this->localizeScripts[] = [$name, $variable, $data];
    }

    return $this;
  }

  /**
   * Load new Javascript (React bundle) resource in admin area.
   *
   * @param string $name      Script handle the data will be attached to.
   * @param bool   $module    Optional. There is a module css.
   * @param string $variable  Optional. Name for the JavaScript object
   * @param array  $data      Optional. The data itself. The data can be either a single or multi-dimensional array.
   *
   * @deprecated 1.6.0
   *
   * @return $this
   */
  public function withAdminAppsScripts($name, $deps = [], $ver = []): View
  {
    _deprecated_function(__METHOD__, '1.6.0', 'withAdminAppsScript()');

    return $this->withAdminAppsScript($name, $deps, $ver);
  }

  /**
   * Load a new css resource in theme.
   *
   * @param string $name Name of style.
   * @param array  $deps Optional. Array of slug deps
   * @param array  $ver  Optional. Version.
   *
   * @return $this
   */
  public function withStyle($name, $deps = [], $ver = []): View
  {
    $this->styles[] = [$name, $deps, $ver];

    return $this;
  }

  /**
   * Load a new css resource in theme.
   *
   * @param string $name Name of style.
   * @param array  $deps Optional. Array of slug deps
   * @param array  $ver  Optional. Version.
   *
   * @deprecated 1.6.1
   *
   * @return $this
   */
  public function withStyles($name, $deps = [], $ver = []): View
  {
    _deprecated_function(__METHOD__, '1.6.1', 'withStyle()');

    return $this->withStyle($name, $deps, $ver);
  }

  /**
   * Load a new css resource in theme.
   *
   * @param string $name Name of script.
   * @param array  $deps Optional. Array of slug deps
   * @param array  $ver  Optional. Version.
   *
   * @return $this
   */
  public function withScript($name, $deps = [], $ver = []): View
  {
    $this->scripts[] = [$name, $deps, $ver];

    return $this;
  }

  public function withScripts($name, $deps = [], $ver = []): View
  {
    _deprecated_function(__METHOD__, '1.6.1', 'withScript()');

    return $this->withScript($name, $deps, $ver);
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

  /**
   * Enqueue scripts in the admin area.
   * This method is called only if the view is rendered in the admin area.
   * @see View::render()
   */
  protected function admin_enqueue_scripts()
  {
    if (!empty($this->adminScripts)) {
      foreach ($this->adminScripts as $script) {
        $src = $this->container->js . '/' . $script[0] . '.js';
        wp_enqueue_script($script[0], $src, $script[1], $script[2], true);
      }
    }

    if (!empty($this->adminAppsScripts)) {
      foreach ($this->adminAppsScripts as $script) {
        $src = $this->container->apps . '/' . $script[0] . '.js';
        wp_enqueue_script($script[0], $src, $script[1], $script[2], true);
        wp_set_script_translations($script[0], $this->container->TextDomain, $this->container->basePath . '/' . $this->container->DomainPath);
      }
    }

    if (!empty($this->localizeScripts)) {
      foreach ($this->localizeScripts as $script) {
        wp_localize_script($script[0], $script[1], $script[2]);
      }
    }

    if (!empty($this->inlineScripts)) {
      foreach ($this->inlineScripts as $script) {
        wp_add_inline_script($script[0], $script[1], $script[2]);
      }
    }
  }

  /**
   * Enqueue styles in the admin area.
   * This method is called only if the view is rendered in the admin area.
   * @see View::render()
   */
  protected function admin_print_styles()
  {
    if (!empty($this->adminStyles)) {
      foreach ($this->adminStyles as $style) {
        $src = $this->container->css . '/' . $style[0] . '.css';
        wp_enqueue_style($style[0], $src, $style[1], $style[2]);
      }
    }

    if (!empty($this->adminAppsModules)) {
      foreach ($this->adminAppsModules as $script) {
        $src = $this->container->apps . '/' . $script[0] . '.css';
        wp_enqueue_style($script[0], $src);
      }
    }

    if (!empty($this->inlineStyles)) {
      foreach ($this->inlineStyles as $style) {
        wp_add_inline_style($style[0], $style[1]);
      }
    }
  }

  /**
   * Enqueue scripts in the theme.
   * This method is called only if the view is rendered in the theme.
   * @see View::render()
   */
  protected function wp_enqueue_scripts()
  {
    if (!empty($this->scripts)) {
      foreach ($this->scripts as $script) {
        $src = $this->container->js . '/' . $script[0] . '.js';
        wp_enqueue_script($script[0], $src, $script[1], $script[2], true);
      }
    }

    if (!empty($this->localizeScripts)) {
      foreach ($this->localizeScripts as $script) {
        wp_localize_script($script[0], $script[1], $script[2]);
      }
    }

    if (!empty($this->inlineScripts)) {
      foreach ($this->inlineScripts as $script) {
        wp_add_inline_script($script[0], $script[1], $script[2]);
      }
    }
  }

  /**
   * Enqueue styles in the theme.
   * This method is called only if the view is rendered in the theme.
   * @see View::render()
   */
  protected function wp_print_styles()
  {
    if (!empty($this->styles)) {
      foreach ($this->styles as $style) {
        $src = $this->container->css . '/' . $style[0] . '.css';
        wp_enqueue_style($style[0], $src, $style[1], $style[2]);
      }
    }

    if (!empty($this->inlineStyles)) {
      foreach ($this->inlineStyles as $style) {
        wp_add_inline_style($style[0], $style[1]);
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
    // This is for backward compatibility with view that not use blade.
    $exts = ['.blade.php', '.php'];

    foreach ($exts as $ext) {
      if (
        file_exists($this->container->basePath . '/resources/views/' . str_replace('.', '/', $this->key) . $ext)
      ) {
        return str_replace('.', '/', $this->key) . $ext;
      }
    }

    return str_replace('.', '/', $this->key) . '.php';
  }
}
