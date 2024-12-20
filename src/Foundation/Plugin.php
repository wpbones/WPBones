<?php

namespace WPKirk\WPBones\Foundation;

use Closure;
use WPKirk\WPBones\Container\Container;
use WPKirk\WPBones\Contracts\Foundation\Plugin as PluginContract;
use WPKirk\WPBones\Database\WordPressOption;
use WPKirk\WPBones\Foundation\Http\Request;
use WPKirk\WPBones\Foundation\Log\LogServiceProvider;
use WPKirk\WPBones\Routing\AdminMenuProvider;
use WPKirk\WPBones\Routing\AdminRouteProvider;
use WPKirk\WPBones\Routing\API\RestProvider;
use WPKirk\WPBones\Routing\Pages\PageProvider;
use WPKirk\WPBones\Support\Str;
use WPKirk\WPBones\Support\Traits\HasAttributes;
use WPKirk\WPBones\View\View;
use WPKirk\WPBones\Database\Eloquent;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Main Plugin class.
 *
 * @package WPKirk\WPBones\Foundation
 */
class Plugin extends Container implements PluginContract
{
  use HasAttributes;

  /**
   * The current globally available container (if any).
   *
   * @var Plugin
   */
  protected static $instance;

  /**
   * The slug of this plugin.
   *
   * @var string
   */
  public $slug = '';

  /**
   * Build in __FILE__ relative plugin.
   *
   * @var string
   */
  protected $file;

  /**
   * The base path for the plugin installation.
   *
   * @var string
   */
  protected $basePath;

  /**
   * The base uri for the plugin installation.
   *
   * @var string
   */
  protected $baseUri;

  /**
   * Internal use where store the plugin data.
   *
   * @var array
   */
  protected $pluginData = [];

  /**
   * A key value pairs array with the list of providers.
   *
   * @var array
   */
  protected $provides = [];
  private $_options = null;
  private $_request = null;

  public function __construct($basePath)
  {
    $this->basePath = rtrim($basePath, '\/');

    $this->boot();
  }

  /**
   * Boot the plugin.
   *
   * @access private
   *
   * @return Plugin
   */
  public function boot(): Plugin
  {
    // simulate __FILE__
    $this->file = $this->basePath . '/wp-kirk.php';

    $this->baseUri = rtrim(plugin_dir_url($this->file), '\/');

    // Activation & Deactivation Hook
    register_activation_hook($this->file, [$this, '_activation']);
    register_deactivation_hook($this->file, [$this, '_deactivation']);

    // handle plugin update
    add_filter('upgrader_post_install', [$this, '_upgrader_post_install'], 10, 3);

    /**
     * There are many pitfalls to using the uninstall hook. It ’ s a much cleaner, and easier, process to use the
     * uninstall.php method for removing plugin settings and options when a plugin is deleted in WordPress.
     *
     * Using uninstall.php file. This is typically the preferred method because it keeps all your uninstall code in a
     * separate file. To use this method, create an uninstall.php file and place it in the root directory of your
     * plugin. If this file exists WordPress executes its contents when the plugin is deleted from the WordPress
     * Plugins screen page.
     *
     */

    // register_uninstall_hook( $file, array( $this, 'uninstall' ) );

    // Log
    $this->provides['Log'] = (new LogServiceProvider($this))->register();

    // init Eloquent out of box
    Eloquent::init();

    // init api
    $this->initApi();

    // Fires after WordPress has finished loading but before any headers are sent.
    add_action('init', [$this, '_init']);

    // Fires before the administration menu loads in the admin.
    add_action('admin_menu', [$this, '_admin_menu']);

    // Fires after all default WordPress widgets have been registered.
    add_action('widgets_init', [$this, '_widgets_init']);

    // Filter a screen option value before it is set.
    add_filter('set-screen-option', [$this, '_set_screen_option'], 10, 3);

    static::$instance = $this;

    return $this;
  }

  /**
   * Init the Rest API Provider
   *
   * @access private
   * @return void
   */
  private function initApi()
  {
    (new RestProvider($this))->register();
  }

  /**
   * Init some data by getting the plugin header information.
   *
   * @access private
   * @return void
   */
  private function initPluginData()
  {
    // Use WordPress get_plugin_data() function for auto retrieve plugin information.
    if (!function_exists('get_plugin_data')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    // Starting from WordPress 6.7, the third parameter (translate) must be set to false.
    // Otherwise, the plugin data will be translated.
    // https://make.wordpress.org/core/2024/10/21/i18n-improvements-6-7/
    $this->pluginData = get_plugin_data($this->file, false, false);

    /**
     * In $this->pluginData you'll find all WordPress
     *
     * Author = "Giovambattista Fazioli"
     * AuthorName = "Giovambattista Fazioli"
     * AuthorURI = "https://wpbones.com/"
     * Description = "WPKirk is a WP Bones boilerplate plugin"
     * DomainPath = "languages"
     * Name = "WPKirk"
     * Network = false
     * PluginURI = "https://wpbones.com/"
     * TextDomain = "wp-kirk"
     * Title = "WPKirk"
     * Version = "1.0.0"
     */

    // plugin slug
    $this->slug = str_replace('-', '_', sanitize_title($this->pluginData['Name'])) . '_slug';
  }

  /**
   * Fires after WordPress has finished loading but before any headers are sent.
   *
   * Most of WP is loaded at this stage, and the user is authenticated. WP continues
   * to load on the init hook that follows (e.g. widgets), and many plugins instantiate
   * themselves on it for all sorts of reasons (e.g. they need a user, a taxonomy, etc.).
   *
   * If you wish to plug an action once WP is loaded, use the wp_loaded hook below.
   *
   * @access private
   * @since 1.8.0 - Sequence
   *
   * - Load all available hooks (in /plugin/hooks)
   * - Custom post types Service Provider
   * - Custom taxonomy type Service Provider
   * - Custom shortcodes Service Provider
   * - Custom Ajax Service Provider
   * - Custom Services Service Provider
   *
   */
  public function _init()
  {
    // Use WordPress get_plugin_data() function for auto retrieve plugin information.
    if (!function_exists('get_plugin_data')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $this->pluginData = get_plugin_data($this->file, false, false);

    /**
     * In $this->pluginData you'll find all WordPress
     *
     * Author = "Giovambattista Fazioli"
     * AuthorName = "Giovambattista Fazioli"
     * AuthorURI = "https://wpbones.com/"
     * Description = "WPKirk is a WP Bones boilerplate plugin"
     * DomainPath = "languages"
     * Name = "WPKirk"
     * Network = false
     * PluginURI = "https://wpbones.com/"
     * TextDomain = "wp-kirk"
     * Title = "WPKirk"
     * Version = "1.0.0"
     */

    // plugin slug
    $this->slug = str_replace('-', '_', sanitize_title($this->pluginData['Name'])) . '_slug';

    // Load plugin text domain
    load_plugin_textdomain(
      'wp-kirk',
      false,
      trailingslashit(basename($this->basePath)) . $this->pluginData['DomainPath']
    );


    // Load all available hooks
    // @since 1.8.0
    if (is_dir($this->basePath . '/plugin/hooks')) {
      array_map(function ($file) {
        if (!is_dir($file)) {
          require_once $file;
        }
      }, glob($this->basePath . '/plugin/hooks/' . '*.php', GLOB_MARK));
    }

    // Custom post types Service Provider
    $custom_post_types = $this->config('plugin.custom_post_types', []);
    foreach ($custom_post_types as $className) {
      $object = new $className($this);
      $object->register();
      $this->provides[$className] = $object;
    }

    // Custom taxonomy type Service Provider
    $custom_taxonomy_types = $this->config('plugin.custom_taxonomy_types', []);
    foreach ($custom_taxonomy_types as $className) {
      $object = new $className($this);
      $object->register();
      $this->provides[$className] = $object;
    }

    // Shortcodes Service Provider
    $shortcodes = $this->config('plugin.shortcodes', []);
    foreach ($shortcodes as $className) {
      $object = new $className($this);
      $object->register();
      $this->provides[$className] = $object;
    }

    // Ajax Service Provider
    if ($this->isAjax()) {
      $ajax = $this->config('plugin.ajax', []);
      foreach ($ajax as $className) {
        $object = new $className($this);
        $object->register();
        $this->provides[$className] = $object;
      }
    }

    // Custom service provider
    $providers = $this->config('plugin.providers', []);
    foreach ($providers as $className) {
      $object = new $className($this);
      $object->register();
      $this->provides[$className] = $object;
    }
  }

  /**
   * Fires before the administration menu loads in the admin.
   *
   * @access private
   */
  public function _set_screen_option($status, $option, $value)
  {
    if (in_array($option, array_values($this->config('plugin.screen_options', [])))) {
      return $value;
    }

    return $status;
  }

  /**
   * Get / set the specified configuration value.
   *
   * If an array is passed as the key, we will assume you want to set an array of values.
   *
   * @param array|string $key The key of the configuration in dot notation.
   * @param mixed $default    Optional. Default value
   *
   * @return mixed
   */
  public function config($key = null, $default = null)
  {
    if (is_null($key)) {
      return [];
    }

    $parts = explode('.', $key);

    $filename = "{$parts[0]}.php";
    $key = $parts[1] ?? null;

    $array = include "{$this->basePath}/config/{$filename}";

    if (is_null($key)) {
      return $array;
    }

    unset($parts[0]);

    foreach ($parts as $segment) {
      if (!is_array($array) || !array_key_exists($segment, $array)) {
        return wpbones_value($default);
      }

      $array = $array[$segment];
    }

    return $array;
  }

  /**
   * Return the Log provider
   *
   * @return mixed
   */
  public function log()
  {
    return $this->provides['Log'];
  }


  /**
   * Returns the absolute URL for the vendor directory.
   *
   * @param string $vendor Optional. Default 'wpbones'.
   *
   * @return string
   */
  public function vendor($vendor = 'wpbones'): string
  {
    return "{$this->baseUri}/vendor/{$vendor}";
  }

  /**
   * Gets the value of an environment variable. Supports boolean, empty and null.
   *
   * @param string $key     The environment variable name.
   * @param mixed $default  Optional. Default null.
   *
   * @return mixed
   */
  public function env($key, $default = null)
  {
    return wpbones_env($key, $default);
  }

  /**
   * Return an instance of View/Contract.
   *
   * @param null  $key  Optional. Default null.
   * @param array $data Optional. Default null.
   *
   * @return View
   */
  public function view($key = null, $data = []): View
  {
    $view = new View($this, $key, $data);

    return $view;
  }

  /**
   * Return a provider by name
   *
   * @param string  $name The Class name of the provider.
   *
   * @return mixed|null
   */
  public function provider($name)
  {
    if (in_array($name, array_keys($this->provides))) {
      return $this->provides[$name];
    }

    return null;
  }

  /**
   * Helper method to load (enqueue) styles.
   *
   * For your convenience, the params $filename may be an array of file.
   *
   * @param string|array  $filename  Filename
   * @param array         $deps      Optional. Dependencies array
   * @param null          $version   Optional. Default plugin version
   */
  public function css($filename, $deps = [], $version = null)
  {
    $filenames = (array)$filename;

    foreach ($filenames as $file) {
      wp_enqueue_style(
        $this->slug . Str::slug($file),
        $this->css . '/' . $file,
        (array)$deps,
        $version ?? $this->Version
      );
    }
  }

  /**
   * Helper method to load (enqueue) styles.
   *
   * For your convenience, the params $filename may be an array of file.
   *
   * @param string|array  $filename Filenames
   * @param array         $deps     Optional. Dependencies array
   * @param null          $version  Optional. Default plugin version
   * @param bool          $footer   Optional. Load on footer. Default true
   */
  public function js($filename, $deps = [], $version = null, $footer = true)
  {
    $filenames = (array)$filename;

    foreach ($filenames as $file) {
      wp_enqueue_script(
        $this->slug . Str::slug($file),
        WPKirk()->js . '/' . $file,
        (array)$deps,
        $version ?? $this->Version,
        $footer
      );
    }
  }

  /**
   * Return TRUE if an Ajax called
   *
   * @return bool
   */
  public function isAjax(): bool
  {
    if (defined('DOING_AJAX')) {
      return true;
    }
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      return true;
    }

    return false;
  }

  /*
  |--------------------------------------------------------------------------
  | WordPress actions & filter
  |--------------------------------------------------------------------------
  |
  | When a plugin starts we will use some useful actions and filters.
  |
  */

  /**
   * Called when a plugin is updated; `upgrader_post_install`
   *
   * @param $response
   * @param $hook_extra
   * @param $result
   *
   * @access private
   *
   * @return mixed
   */
  public function _upgrader_post_install($response, $hook_extra, $result)
  {
    // Check if the action is an update for a plugin
    if (isset($hook_extra['plugin'])) {
      // Verify if the updated plugin is the specific one
      if ($hook_extra['plugin'] == plugin_basename($this->file)) {
        // Call the update function
        // include your own activation
        $updated = include_once "{$this->basePath}/plugin/updated.php";

        // updates/align the plugin options
        $this->options->delta();

        // migrations
        foreach (glob("{$this->basePath}/database/migrations/*.php") as $filename) {
          $instance = include $filename;
        }

        // seeders
        foreach (glob("{$this->basePath}/database/seeders/*.php") as $filename) {
          $instance = include $filename;
        }
      }
    }

    return $response;
  }

  /**
   * Called when a plugin is activated; `register_activation_hook()`
   *
   * @access private
   */
  public function _activation()
  {
    // updates/align the plugin options
    $this->options->delta();

    // include your own activation
    $activation = include_once "{$this->basePath}/plugin/activation.php";

    // migrations
    foreach (glob("{$this->basePath}/database/migrations/*.php") as $filename) {
      $instance = include $filename;
    }

    // seeders
    foreach (glob("{$this->basePath}/database/seeders/*.php") as $filename) {
      $instance = include $filename;
    }
  }

  /**
   * Called when a plugin is deactivated; `register_deactivation_hook()`
   *
   * @access private
   */
  public function _deactivation()
  {
    $deactivation = include_once "{$this->basePath}/plugin/deactivation.php";
  }

  /**
   * Fires before the administration menu loads in the admin.
   *
   * @access private
   */
  public function _admin_menu()
  {
    // register the admin menu
    (new AdminMenuProvider($this))->register();

    // register the admin custom pages
    (new AdminRouteProvider($this))->register();

    // register the custom pages via folder
    (new PageProvider($this))->register();
  }

  /**
   * Register the Widgets
   *
   * @access private
   */
  public function _widgets_init()
  {
    global $wp_widget_factory;

    $widgets = $this->config('plugin.widgets', []);

    foreach ($widgets as $className) {
      //register_widget($className);
      $wp_widget_factory->widgets[$className] = new $className($this);
    }
  }

  /**
   * Dynamically retrieves some attributes (magic getter).
   * If getter methods exist for the attributes, it calls them and returns the value.
   * If attributes exist in pluginData, it returns them.
   *
   * @param string $name
   *
   * @return mixed
   */
  public function __get($name)
  {
    if ($this->hasGetMutator($name)) {
      return $this->mutateAttribute($name);
    }

    if (in_array($name, array_keys($this->pluginData))) {
      return $this->pluginData[$name];
    }
  }

  /**
   * Get the base path of the plugin installation.
   *
   * @deprecated 1.6.0 Use basePath instead
   * @return string
   */
  public function getBasePath(): string
  {
    _deprecated_function(__METHOD__, '1.6.0', 'basePath');
    return $this->basePath;
  }

  /**
   * Return the absolute URL for the installation plugin.
   *
   * Example: http://example.com/wp-content/plugins/plugin-name
   *
   * @deprecated 1.6.0 Use baseUri instead
   *
   * @return string
   */
  public function getBaseUri(): string
  {
    _deprecated_function(__METHOD__, '1.6.0', 'baseUri');
    return $this->baseUri;
  }

  /**
   * Return the URL of a custom page
   *
   * @param string  $pageSlug The slug of the page
   *
   * @return string
   */
  public function getPageUrl($pageSlug): string
  {
    return add_query_arg(['page' => $pageSlug], admin_url('admin.php'));
  }

  /**
   * Return the URL of a menu page
   *
   * @param string|int  $menuSlug The slug of the menu. The array key used in the menu array.
   *
   * @return string
   */
  public function getMenuUrl($menuSlug): string
  {
    $array = explode('\\', __NAMESPACE__);
    $namespace = sanitize_title($array[0]);
    return add_query_arg(['page' => "{$namespace}_{$menuSlug}"], admin_url('admin.php'));
  }

  /**
   * Utility method to get the callback for a route
   *
   * @param array $routes
   * @return Closure|null
   */
  public function getCallableHook($routes)
  {
    // get the http request verb
    $verb = $this->request->method;

    if (isset($routes['resource'])) {
      $methods = [
        'get' => 'index',
        'post' => 'store',
        'put' => 'update',
        'patch' => 'update',
        'delete' => 'destroy',
      ];

      $controller = $routes['resource'];
      $method = $methods[$verb];
    } // by single verb and controller@method
    else {
      if (isset($routes[$verb])) {
        [$controller, $method] = Str::parseCallback($routes[$verb]);
      } // default "get"
      else {
        if (isset($routes['get'])) {
          [$controller, $method] = Str::parseCallback($routes['get']);
        }
      }
    }

    if (isset($controller) && isset($method)) {
      return function () use ($controller, $method) {
        $className = "WPKirk\\Http\\Controllers\\{$controller}";
        $instance = new $className();

        if (method_exists($instance, 'render')) {
          return $instance->render("{$method}");
        }
      };
    }

    return null;
  }

  /**
   * Return the list of classes in a PHP file.
   *
   * @param string $filename A PHP Filename file.
   *
   * @return array|bool
   *
   * @suppress PHP0415
   */
  private function getFileClasses($filename)
  {
    $code = file_get_contents($filename);

    if (empty($code)) {
      return false;
    }

    $classes = [];
    $tokens = token_get_all($code);
    $count = count($tokens);
    for ($i = 2; $i < $count; $i++) {
      if ($tokens[$i - 2][0] == T_CLASS && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) {
        $class_name = $tokens[$i][1];
        $classes[] = $class_name;
      }
    }

    return $classes;
  }

  /**
   * Return the plugin options
   *
   * See the Options <https://wpbones.com/docs/CoreConcepts/options> documentation for more information.
   *
   * @return mixed
   */
  protected function getOptionsAttribute(): WordPressOption
  {
    if (is_null($this->_options)) {
      $this->_options = new WordPressOption($this);
    }

    return $this->_options;
  }

  /**
   * Return the request
   *
   * @return Request
   */
  protected function getRequestAttribute(): Request
  {
    if (is_null($this->_request)) {
      $this->_request = new Request();
    }

    return $this->_request;
  }

  /**
   * Return the plugin basename
   *
   * @example my-plugin/my-plugin.php
   *
   * @return string
   */
  protected function getPluginBasenameAttribute(): string
  {
    return plugin_basename($this->file);
  }

  /**
   * Return the public css URL
   *
   * @example http://example.com/wp-content/plugins/my-plugin/public/css
   *
   * @return string
   */
  protected function getCssAttribute(): string
  {
    return "{$this->baseUri}/public/css";
  }

  /**
   * Return the public js URL
   *
   * @example http://example.com/wp-content/plugins/my-plugin/public/js
   *
   * @return string
   */
  protected function getJsAttribute(): string
  {
    return "{$this->baseUri}/public/js";
  }

  /**
   * Return the public apps URL
   *
   * @example http://example.com/wp-content/plugins/my-plugin/public/apps
   *
   * @return string
   */
  protected function getAppsAttribute(): string
  {
    return "{$this->baseUri}/public/apps";
  }

  /**
   * Return the public images URL
   *
   * @example http://example.com/wp-content/plugins/my-plugin/public/images
   *
   * @return string
   */
  protected function getImagesAttribute(): string
  {
    return "{$this->baseUri}/public/images";
  }

  /**
   * Return the filesystem path to the plugin
   *
   * @example /var/www/html/wp-content/plugins/my-plugin
   *
   * @return string
   */
  protected function getBasePathAttribute(): string
  {
    return $this->basePath;
  }

  /**
   * Return the base URI of the plugin
   *
   * @example http://example.com/wp-content/plugins/my-plugin
   *
   * @return string
   */
  protected function getBaseUriAttribute(): string
  {
    return $this->baseUri;
  }

  /**
   * Return the plugin file.
   * This is an alias of `__FILE__`
   *
   * @example /var/www/html/wp-content/plugins/my-plugin/my-plugin.php
   *
   * @since 1.8.0
   *
   * @return string
   */
  protected function getFileAttribute(): string
  {
    return $this->file;
  }
}
