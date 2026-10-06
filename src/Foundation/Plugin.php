<?php

namespace WPKirk\WPBones\Foundation;

use Closure;
use WPKirk\WPBones\Container\Container;
use WPKirk\WPBones\Contracts\Foundation\Plugin as PluginContract;
use WPKirk\WPBones\Database\Migrations\MigrationResult;
use WPKirk\WPBones\Database\Migrations\Migrator;
use WPKirk\WPBones\Database\Migrations\OptionRepository;
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
  private $_migrator = null;

  public function __construct($basePath)
  {
    $this->basePath = rtrim($basePath, '\/');

    $this->boot();
  }

  /**
   * The instance Plugin::boot() registered, or null before bootstrap/plugin.php has run.
   *
   * Code that runs after WordPress has loaded the plugin, such as a bones console command,
   * reaches the existing instance through this instead of building a second one.
   */
  public static function getInstance(): ?Plugin
  {
    return static::$instance;
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

    // Migrations, and the end of an update: before the plugin's own init (10, unless
    // config/plugin.php sets another priority).
    // Not on plugins_loaded: a migration that seeds translated text would load the text domain
    // before after_setup_theme, which WordPress 6.7 reports as too early.
    add_action('init', [$this, '_migrate'], 1);

    // Tell the administrators when a migration failed.
    add_action('admin_notices', [$this, '_migration_notice']);

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

    // Fetch the priorities from the config file
    $priorities = $this->config('plugin.priorities', []);

    // Fires after WordPress has finished loading but before any headers are sent.
    add_action('init', [$this, '_init'], $priorities['init'] ?? 10);

    // Fires before the administration menu loads in the admin.
    add_action('admin_menu', [$this, '_admin_menu'], $priorities['admin_init'] ?? 10);

    // Fires after all default WordPress widgets have been registered.
    add_action('widgets_init', [$this, '_widgets_init'], $priorities['widget_init'] ?? 10);

    // Filter a screen option value before it is set.
    add_filter('set-screen-option', [$this, '_set_screen_option'], $priorities['set_screen_option'] ?? 10, 3);

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
    // The header, unless the migrations on init already read it in this request.
    if (empty($this->pluginData)) {
      $this->initPluginData();
    }

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
   * The plugin's migration runner (wpbones/WPBones#40).
   *
   * @since 3.0.0
   */
  public function migrator(): Migrator
  {
    if (is_null($this->_migrator)) {
      // The header is read on init; activation and the migrations can come earlier.
      if (empty($this->pluginData)) {
        $this->initPluginData();
      }

      $this->_migrator = new Migrator(
        "{$this->basePath}/database/migrations",
        (string) $this->Version,
        new OptionRepository($this->slug),
        (string) $this->Name
      );
    }

    return $this->_migrator;
  }

  /**
   * Run the migrations when the plugin's version differs from the one the database was migrated
   * for: after an update, whichever way it arrived (the dashboard, an uploaded ZIP, FTP, git,
   * Composer), and on every site of a network the first time it loads a page. This request then
   * also finishes the update: the options delta and plugin/updated.php.
   *
   * Hooked on `init`. Until 2.x an update ran everything from `upgrader_post_install`, which runs
   * in the request doing the update with the old code still loaded, and never for a ZIP replaced
   * through "Upload Plugin" or for files copied by other means.
   *
   * @access private
   * @since 3.0.0
   */
  public function _migrate()
  {
    // WordPress checks a fresh auto-update by loading the home page with this key, giving it 50
    // seconds: a slow migration there would get the new code rolled back over a migrated database.
    // The next request runs them.
    if (isset($_REQUEST['wp_scrape_key']) || wp_installing()) {
      return;
    }

    // `php bones migrate` loads WordPress and then runs them itself, so that it can tell what ran.
    if (defined('WPBONES_COMMAND_LINE_VERSION')) {
      return;
    }

    $byAdministrator = is_admin() && current_user_can('manage_options');

    if (!$this->migrator()->isDue($byAdministrator)) {
      return;
    }

    $this->runMigrations(true, $byAdministrator);
  }

  /**
   * Run the pending migrations and, in the request that moved the stored version, finish the
   * update: align the options and run plugin/updated.php, which receives the version it came from
   * as $previousVersion. On a site migrated for the first time there is no previous version, and
   * plugin/updated.php does not run. Activation and `php bones migrate` call this too, and run
   * whatever the state; a page load runs it as $automatic, see Migrator::migrate().
   *
   * @since 3.0.0
   */
  public function runMigrations(bool $automatic = false, bool $byAdministrator = false): MigrationResult
  {
    $this->warnAboutSeeders();

    $result = $this->migrator()->migrate($automatic, $byAdministrator);

    $this->finishMigration($result);

    return $result;
  }

  /**
   * A plugin moved to 3.0 without `php bones migrate:to-v3` still has seeders that never run.
   */
  protected function warnAboutSeeders(): void
  {
    if (!glob("{$this->basePath}/database/seeders/*.php")) {
      return;
    }

    if (empty($this->pluginData)) {
      $this->initPluginData();
    }

    error_log(
      sprintf(
        '[WP Bones] %s: database/seeders/ does not run since WP Bones 3.0; php bones migrate:to-v3 turns the seeders into migrations',
        $this->Name
      )
    );
  }

  protected function finishMigration(MigrationResult $result): void
  {
    if (!$result->advanced) {
      return;
    }

    $this->options->delta();

    if ($result->previous !== null && file_exists("{$this->basePath}/plugin/updated.php")) {
      $previousVersion = $result->previous;

      include "{$this->basePath}/plugin/updated.php";
    }
  }

  /**
   * Show the administrators the migration that failed, until it runs.
   *
   * @access private
   * @since 3.0.0
   */
  public function _migration_notice()
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    // An autoloaded option: no query on every admin page.
    $failure = $this->migrator()->failure();

    if ($failure === null) {
      return;
    }

    printf(
      '<div class="notice notice-error"><p><strong>%1$s</strong>: the database migration <code>%2$s</code> failed, and the ones after it have not run: %3$s</p><p>Fix the cause and reload this page, or run <code>php bones migrate</code> from the plugin folder.</p></div>',
      esc_html((string) $this->Name),
      esc_html((string) ($failure['migration'] ?? '')),
      esc_html((string) ($failure['message'] ?? ''))
    );
  }

  /**
   * Called when a plugin is activated; `register_activation_hook()`
   *
   * Runs the pending migrations whatever the stored version says: activation is also how a
   * migration added during development, without a version bump, gets run. A plugin updated while
   * inactive finishes its update here: plugin/updated.php runs, with the version it came from.
   *
   * @access private
   */
  public function _activation()
  {
    // Activation comes after init, which is where the header is read (#51): without this the
    // slug was empty here, so the options delta below wrote to an options row with no name, and
    // $this->Version was null in plugin/activation.php.
    if (empty($this->pluginData)) {
      $this->initPluginData();
    }

    // updates/align the plugin options
    $this->options->delta();

    // include your own activation
    $activation = include_once "{$this->basePath}/plugin/activation.php";

    $this->runMigrations();
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
