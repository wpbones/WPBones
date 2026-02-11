<?php

namespace WPKirk\WPBones\View;

use eftec\bladeone\BladeOne;
use WPKirk\WPBones\View\Assets\AssetManager;
use WPKirk\WPBones\View\Assets\AdminAssetEnqueuer;
use WPKirk\WPBones\View\Assets\AdminAppsAssetEnqueuer;
use WPKirk\WPBones\View\Assets\FrontendAssetEnqueuer;

/**
 * View class
 *
 * Handles the rendering of views using Blade templating engine or plain PHP files.
 * Manages the enqueueing of assets (CSS/JS) for both admin and frontend areas.
 * Supports inline scripts/styles, script localization, and React app bundles.
 *
 * @package WPKirk\WPBones\View
 */
class View
{
  /**
   * Plugin instance container.
   *
   * @var mixed
   */
  protected $container;

  /**
   * View data to pass to the template.
   *
   * @var array
   */
  protected $data;

  /**
   * View key/path (e.g., 'admin.dashboard').
   *
   * @var string|null
   */
  protected $key;

  /**
   * Asset manager for admin area.
   *
   * @var AssetManager
   */
  protected AssetManager $adminAssets;

  /**
   * Asset manager for frontend area.
   *
   * @var AssetManager
   */
  protected AssetManager $frontendAssets;

  /**
   * Asset manager for admin apps (React bundles).
   *
   * @var AssetManager
   */
  protected AssetManager $adminAppsAssets;

  /**
   * BladeOne instance for template rendering.
   *
   * @var BladeOne
   */
  protected BladeOne $blade;

  /**
   * Legacy property for backward compatibility.
   * List of styles to enqueue in admin area.
   *
   * @deprecated Use adminAssets instead
   * @var array
   */
  protected $adminStyles = [];

  /**
   * Legacy property for backward compatibility.
   * List of scripts to enqueue in admin area.
   *
   * @deprecated Use adminAssets instead
   * @var array
   */
  protected $adminScripts = [];

  /**
   * Legacy property for backward compatibility.
   * List of app scripts to enqueue in admin area.
   *
   * @deprecated Use adminAppsAssets instead
   * @var array
   */
  protected $adminAppsScripts = [];

  /**
   * Legacy property for backward compatibility.
   * List of app modules (CSS) to enqueue in admin area.
   *
   * @deprecated Use adminAppsAssets instead
   * @var array
   */
  protected $adminAppsModules = [];

  /**
   * Legacy property for backward compatibility.
   * List of styles to enqueue in frontend.
   *
   * @deprecated Use frontendAssets instead
   * @var array
   */
  protected $styles = [];

  /**
   * Legacy property for backward compatibility.
   * List of scripts to enqueue in frontend.
   *
   * @deprecated Use frontendAssets instead
   * @var array
   */
  protected $scripts = [];

  /**
   * Legacy property for backward compatibility.
   * List of scripts to localize.
   *
   * @deprecated Use adminAssets or frontendAssets instead
   * @var array
   */
  protected $localizeScripts = [];

  /**
   * Legacy property for backward compatibility.
   * List of inline scripts.
   *
   * @deprecated Use adminAssets or frontendAssets instead
   * @var array
   */
  protected $inlineScripts = [];

  /**
   * Legacy property for backward compatibility.
   * List of inline styles.
   *
   * @deprecated Use adminAssets or frontendAssets instead
   * @var array
   */
  protected $inlineStyles = [];

  /**
   * Create a new View instance.
   *
   * @param mixed       $container Plugin container instance.
   * @param string|null $key       Optional. The view path (e.g., 'admin.dashboard').
   * @param array       $data      Optional. Data to pass to the view.
   */
  public function __construct($container, ?string $key = null, array $data = [])
  {
    $this->container = $container;
    $this->key = $key;
    $this->data = $data;

    // Initialize asset managers
    $this->adminAssets = new AssetManager();
    $this->frontendAssets = new AssetManager();
    $this->adminAppsAssets = new AssetManager();

    $this->initializeBlade();
  }

  /**
   * Initialize the Blade templating engine.
   *
   * @return void
   */
  protected function initializeBlade(): void
  {
    $cache = $this->container->basePath . '/.cache';

    if (!file_exists($cache)) {
      mkdir($cache, 0777, true);
    }

    // Initialize BladeOne
    $this->blade = new BladeOne(
      $this->container->basePath . '/resources/views',
      $cache,
      BladeOne::MODE_AUTO
    );
  }

  /**
   * Convert the view to a string.
   *
   * @return string The rendered view content.
   */
  public function __toString(): string
  {
    return (string) $this->render();
  }

  /**
   * Render the view.
   *
   * @param bool $asHTML Optional. Set to true to return the content as HTML string. Default false.
   *
   * @return mixed The rendered view or callable.
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
      $func = function () {
        // Make available Html as facade
        if (!class_exists('WPKirk\Html')) {
          class_alias('\WPKirk\WPBones\Html\Html', 'WPKirk\Html', true);
        }

        // Make available plugin instance
        $plugin = $this->container;

        // Make available passing params
        if (!is_null($this->data) && is_array($this->data)) {
          foreach ($this->data as $var) {
            extract($var);
          }
        }

        // Include view
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
   * Return the content of view as HTML string.
   *
   * @return string The rendered view content.
   */
  public function toHTML(): string
  {
    ob_start();

    $this->render();

    $content = ob_get_contents();
    ob_end_clean();

    return $content;
  }

  // ==========================================================================
  // Asset Management Methods
  // ==========================================================================

  /**
   * Add a localized script.
   *
   * @param string $handle The script handle to attach data to.
   * @param string $name   The name of the JavaScript object.
   * @param array  $l10n   The data to localize.
   *
   * @return $this
   */
  public function withLocalizeScript($handle, $name, $l10n): View
  {
    // Add to appropriate asset manager based on context
    if (is_admin()) {
      $this->adminAssets->addLocalizeScript($handle, $name, $l10n);
    } else {
      $this->frontendAssets->addLocalizeScript($handle, $name, $l10n);
    }

    // Maintain backward compatibility with legacy array
    $this->localizeScripts[] = [$handle, $name, $l10n];

    return $this;
  }

  /**
   * Add localized scripts.
   *
   * @param string $handle The script handle to attach data to.
   * @param string $name   The name of the JavaScript object.
   * @param array  $l10n   The data to localize.
   *
   * @deprecated 1.6.0 Use withLocalizeScript() instead.
   *
   * @return $this
   */
  public function withLocalizeScripts($handle, $name, $l10n): View
  {
    _deprecated_function(__METHOD__, '1.6.0', 'withLocalizeScript()');

    return $this->withLocalizeScript($handle, $name, $l10n);
  }

  /**
   * Add extra code to a registered script.
   *
   * @param string $name     The script handle to attach inline script to.
   * @param string $data     The JavaScript code to add.
   * @param string $position Optional. Whether to add before or after. Default 'after'.
   *
   * @return $this
   */
  public function withInlineScript($name, $data, $position = 'after'): View
  {
    // Add to appropriate asset manager based on context
    if (is_admin()) {
      $this->adminAssets->addInlineScript($name, $data, $position);
    } else {
      $this->frontendAssets->addInlineScript($name, $data, $position);
    }

    // Maintain backward compatibility with legacy array
    $this->inlineScripts[] = [$name, $data, $position];

    return $this;
  }

  /**
   * Add extra style to a registered stylesheet.
   *
   * @param string $name The style handle to attach inline style to.
   * @param string $data The CSS code to add.
   *
   * @return $this
   */
  public function withInlineStyle($name, $data): View
  {
    // Add to appropriate asset manager based on context
    if (is_admin()) {
      $this->adminAssets->addInlineStyle($name, $data);
    } else {
      $this->frontendAssets->addInlineStyle($name, $data);
    }

    // Maintain backward compatibility with legacy array
    $this->inlineStyles[] = [$name, $data];

    return $this;
  }

  /**
   * Load a CSS resource in admin area.
   *
   * @param string           $name  The style handle/name.
   * @param string[]         $deps  Optional. Array of registered stylesheet handles this stylesheet depends on. Default empty array.
   * @param string|bool|null $ver   Optional. String specifying stylesheet version number. Default false.
   * @param string           $media Optional. The media for which this stylesheet has been defined. Default 'all'.
   *
   * @return $this
   */
  public function withAdminStyle($name, $deps = [], $ver = false, $media = 'all'): View
  {
    $this->adminAssets->addStyle($name, $deps, $ver, $media);

    // Maintain backward compatibility with legacy array
    $this->adminStyles[] = [$name, $deps, $ver, $media];

    return $this;
  }

  /**
   * Load CSS resources in admin area.
   *
   * @param string           $name  The style handle/name.
   * @param string[]         $deps  Optional. Array of registered stylesheet handles this stylesheet depends on. Default empty array.
   * @param string|bool|null $ver   Optional. String specifying stylesheet version number. Default false.
   * @param string           $media Optional. The media for which this stylesheet has been defined. Default 'all'.
   *
   * @deprecated 1.6.0 Use withAdminStyle() instead.
   *
   * @return $this
   */
  public function withAdminStyles($name, $deps = [], $ver = false, $media = 'all'): View
  {
    _deprecated_function(__METHOD__, '1.6.0', 'withAdminStyle()');

    return $this->withAdminStyle($name, $deps, $ver, $media);
  }

  /**
   * Load a JavaScript resource in admin area.
   *
   * @param string           $name The script handle/name.
   * @param string[]         $deps Optional. Array of registered script handles this script depends on. Default empty array.
   * @param string|bool|null $ver  Optional. String specifying script version number. Default false.
   * @param array|bool       $args Optional. Additional script loading strategies or whether to print in footer. Default true.
   *
   * @return $this
   */
  public function withAdminScript($name, $deps = [], $ver = false, $args = true): View
  {
    $this->adminAssets->addScript($name, $deps, $ver, $args);

    // Maintain backward compatibility with legacy array
    $this->adminScripts[] = [$name, $deps, $ver, $args];

    return $this;
  }

  /**
   * Load JavaScript resources in admin area.
   *
   * @param string           $name The script handle/name.
   * @param string[]         $deps Optional. Array of registered script handles this script depends on. Default empty array.
   * @param string|bool|null $ver  Optional. String specifying script version number. Default false.
   * @param array|bool       $args Optional. Additional script loading strategies or whether to print in footer. Default true.
   *
   * @deprecated 1.6.0 Use withAdminScript() instead.
   *
   * @return $this
   */
  public function withAdminScripts($name, $deps = [], $ver = false, $args = true): View
  {
    _deprecated_function(__METHOD__, '1.6.0', 'withAdminScript()');

    return $this->withAdminScript($name, $deps, $ver, $args);
  }

  /**
   * Load a React bundle script in admin area.
   *
   * @param string $name     The script handle/name.
   * @param bool   $module   Optional. Whether there is a module CSS file. Default true.
   * @param string $variable Optional. Name for the JavaScript object to localize. Default empty.
   * @param array  $data     Optional. Data to localize. Default empty array.
   *
   * @return $this
   */
  public function withAdminAppsScript($name, $module = true, $variable = '', $data = []): View
  {
    // Load asset file generated by @wordpress/scripts
    $assetFile = $this->container->basePath . '/public/apps/' . $name . '.asset.php';
    $assetData = file_exists($assetFile) ? @include $assetFile : ['dependencies' => [], 'version' => ''];

    $dependencies = $assetData['dependencies'] ?? ['wp-element'];
    $version = $assetData['version'] ?? '';

    $this->adminAppsAssets->addAppsScript($name, $dependencies, $version);

    // Add module CSS if requested
    if ($module) {
      $this->adminAppsAssets->addAppsStyle($name, [], $version);
    }

    // Add localization if requested
    if ($variable) {
      $this->adminAppsAssets->addLocalizeScript($name, $variable, $data);
    }

    // Maintain backward compatibility with legacy arrays
    $this->adminAppsScripts[] = [$name, $dependencies, $version];
    if ($module) {
      $this->adminAppsModules[] = [$name, [], $version];
    }
    if ($variable) {
      $this->localizeScripts[] = [$name, $variable, $data];
    }

    return $this;
  }

  /**
   * Load React bundle scripts in admin area.
   *
   * @param string $name Script handle/name.
   * @param array  $deps Dependencies (deprecated parameter).
   * @param array  $ver  Version (deprecated parameter).
   *
   * @deprecated 1.6.0 Use withAdminAppsScript() instead.
   *
   * @return $this
   */
  public function withAdminAppsScripts($name, $deps = [], $ver = []): View
  {
    _deprecated_function(__METHOD__, '1.6.0', 'withAdminAppsScript()');

    return $this->withAdminAppsScript($name, $deps, $ver);
  }

  /**
   * Load a CSS resource in theme/frontend.
   *
   * @param string           $name  The style handle/name.
   * @param array            $deps  Optional. Array of slug dependencies. Default empty array.
   * @param string|bool|null $ver   Optional. Version. Default false.
   * @param string           $media Optional. The media for which this stylesheet has been defined. Default 'all'.
   *
   * @return $this
   */
  public function withStyle($name, $deps = [], $ver = false, $media = 'all'): View
  {
    $this->frontendAssets->addStyle($name, $deps, $ver, $media);

    // Maintain backward compatibility with legacy array
    $this->styles[] = [$name, $deps, $ver, $media];

    return $this;
  }

  /**
   * Load CSS resources in theme/frontend.
   *
   * @param string           $name  The style handle/name.
   * @param array            $deps  Optional. Array of slug dependencies. Default empty array.
   * @param string|bool|null $ver   Optional. Version. Default false.
   * @param string           $media Optional. The media for which this stylesheet has been defined. Default 'all'.
   *
   * @deprecated 1.6.1 Use withStyle() instead.
   *
   * @return $this
   */
  public function withStyles($name, $deps = [], $ver = false, $media = 'all'): View
  {
    _deprecated_function(__METHOD__, '1.6.1', 'withStyle()');

    return $this->withStyle($name, $deps, $ver, $media);
  }

  /**
   * Load a JavaScript resource in theme/frontend.
   *
   * @param string           $name The script handle/name.
   * @param array            $deps Optional. Array of slug dependencies. Default empty array.
   * @param string|bool|null $ver  Optional. Version. Default false.
   * @param bool|array       $args Optional. Whether to enqueue in footer or additional args. Default true.
   *
   * @return $this
   */
  public function withScript($name, $deps = [], $ver = false, $args = true): View
  {
    $this->frontendAssets->addScript($name, $deps, $ver, $args);

    // Maintain backward compatibility with legacy array
    $this->scripts[] = [$name, $deps, $ver, $args];

    return $this;
  }

  /**
   * Load JavaScript resources in theme/frontend.
   *
   * @param string           $name The script handle/name.
   * @param array            $deps Optional. Array of slug dependencies. Default empty array.
   * @param string|bool|null $ver  Optional. Version. Default false.
   * @param bool|array       $args Optional. Whether to enqueue in footer or additional args. Default true.
   *
   * @deprecated 1.6.1 Use withScript() instead.
   *
   * @return $this
   */
  public function withScripts($name, $deps = [], $ver = false, $args = true): View
  {
    _deprecated_function(__METHOD__, '1.6.1', 'withScript()');

    return $this->withScript($name, $deps, $ver, $args);
  }

  /**
   * Pass data to the view.
   *
   * @param mixed $data Array of data or key for single value.
   *
   * @return $this
   *
   * @example $instance->with(['foo' => 'bar'])
   * @example $instance->with('foo', 'bar')
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

  // ==========================================================================
  // Asset Enqueueing Methods (Protected)
  // ==========================================================================

  /**
   * Enqueue scripts in the admin area.
   *
   * This method is called only if the view is rendered in the admin area.
   *
   * @see View::render()
   *
   * @return void
   */
  protected function admin_enqueue_scripts()
  {
    // Use new asset managers
    if ($this->adminAssets->hasScripts() || $this->adminAssets->hasLocalizeScripts() || $this->adminAssets->hasInlineScripts()) {
      $enqueuer = new AdminAssetEnqueuer($this->container, $this->adminAssets);
      $enqueuer->enqueueScripts();
    }

    // Use admin apps asset manager
    if ($this->adminAppsAssets->hasScripts() || $this->adminAppsAssets->hasLocalizeScripts() || $this->adminAppsAssets->hasInlineScripts()) {
      $appsEnqueuer = new AdminAppsAssetEnqueuer($this->container, $this->adminAppsAssets);
      $appsEnqueuer->enqueueScripts();
    }

    // Legacy support: handle old array-based properties if they contain data not in new managers
    if (!empty($this->adminScripts)) {
      foreach ($this->adminScripts as $script) {
        $src = $this->container->js . '/' . $script[0] . '.js';
        wp_enqueue_script($script[0], $src, $script[1], $script[2], $script[3]);
      }
    }

    if (!empty($this->adminAppsScripts)) {
      foreach ($this->adminAppsScripts as $script) {
        $src = $this->container->apps . '/' . $script[0] . '.js';
        wp_enqueue_script($script[0], $src, $script[1], $script[2], true);
        wp_set_script_translations(
          $script[0],
          $this->container->TextDomain,
          $this->container->basePath . '/' . $this->container->DomainPath
        );
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
   *
   * This method is called only if the view is rendered in the admin area.
   *
   * @see View::render()
   *
   * @return void
   */
  protected function admin_print_styles()
  {
    // Use new asset managers
    if ($this->adminAssets->hasStyles() || $this->adminAssets->hasInlineStyles()) {
      $enqueuer = new AdminAssetEnqueuer($this->container, $this->adminAssets);
      $enqueuer->enqueueStyles();
    }

    // Use admin apps asset manager
    if ($this->adminAppsAssets->hasStyles() || $this->adminAppsAssets->hasInlineStyles()) {
      $appsEnqueuer = new AdminAppsAssetEnqueuer($this->container, $this->adminAppsAssets);
      $appsEnqueuer->enqueueStyles();
    }

    // Legacy support: handle old array-based properties if they contain data not in new managers
    if (!empty($this->adminStyles)) {
      foreach ($this->adminStyles as $style) {
        $src = $this->container->css . '/' . $style[0] . '.css';
        wp_enqueue_style($style[0], $src, $style[1], $style[2], $style[3]);
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
   * Enqueue scripts in the theme/frontend.
   *
   * This method is called only if the view is rendered in the theme.
   *
   * @see View::render()
   *
   * @return void
   */
  protected function wp_enqueue_scripts()
  {
    // Use new asset manager
    if ($this->frontendAssets->hasScripts() || $this->frontendAssets->hasLocalizeScripts() || $this->frontendAssets->hasInlineScripts()) {
      $enqueuer = new FrontendAssetEnqueuer($this->container, $this->frontendAssets);
      $enqueuer->enqueueScripts();
    }

    // Legacy support: handle old array-based properties if they contain data not in new managers
    if (!empty($this->scripts)) {
      foreach ($this->scripts as $script) {
        $src = $this->container->js . '/' . $script[0] . '.js';
        wp_enqueue_script($script[0], $src, $script[1], $script[2], $script[3]);
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
   * Enqueue styles in the theme/frontend.
   *
   * This method is called only if the view is rendered in the theme.
   *
   * @see View::render()
   *
   * @return void
   */
  protected function wp_print_styles()
  {
    // Use new asset manager
    if ($this->frontendAssets->hasStyles() || $this->frontendAssets->hasInlineStyles()) {
      $enqueuer = new FrontendAssetEnqueuer($this->container, $this->frontendAssets);
      $enqueuer->enqueueStyles();
    }

    // Legacy support: handle old array-based properties if they contain data not in new managers
    if (!empty($this->styles)) {
      foreach ($this->styles as $style) {
        $src = $this->container->css . '/' . $style[0] . '.css';
        wp_enqueue_style($style[0], $src, $style[1], $style[2], $style[3]);
      }
    }

    if (!empty($this->inlineStyles)) {
      foreach ($this->inlineStyles as $style) {
        wp_add_inline_style($style[0], $style[1]);
      }
    }
  }

  /**
   * Get the filename for the view.
   *
   * Checks for both Blade templates (.blade.php) and plain PHP files (.php).
   * This is for backward compatibility with views that don't use Blade.
   *
   * @return string The filename of the view.
   */
  protected function filename(): string
  {
    // This is for backward compatibility with view that not use blade.
    $exts = ['.blade.php', '.php'];

    foreach ($exts as $ext) {
      if (file_exists($this->container->basePath . '/resources/views/' . str_replace('.', '/', $this->key) . $ext)) {
        return str_replace('.', '/', $this->key) . $ext;
      }
    }

    return str_replace('.', '/', $this->key) . '.php';
  }
}
