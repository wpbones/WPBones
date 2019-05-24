<?php

namespace WPKirk\WPBones\Foundation;

use Illuminate\Database\Capsule\Manager as Capsule;
use WPKirk\WPBones\Container\Container;
use WPKirk\WPBones\Contracts\Foundation\Plugin as PluginContract;
use WPKirk\WPBones\Database\WordPressOption;
use WPKirk\WPBones\Foundation\Http\Request;
use WPKirk\WPBones\Foundation\Log\LogServiceProvider;
use WPKirk\WPBones\Support\Str;
use WPKirk\WPBones\View\View;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin extends Container implements PluginContract
{

    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    protected static $instance;
    /**
     * The slug of this plugin.
     *
     * @var string
     */
    public $slug = '';
    /**
     * Buld in __FILE__ relative plugin.
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

    public function boot()
    {
        $this->initEloquent();

        // emule __FILE__
        $this->file = $this->basePath . '/index.php';

        $this->baseUri = rtrim(plugin_dir_url($this->file), '\/');

        // Use WordPress get_plugin_data() function for auto retrive plugin information.
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $this->pluginData = get_plugin_data($this->file, false);

        /*
         * In $this->pluginData you'll find all WordPress
         *
          Author = "Giovambattista Fazioli"
          AuthorName = "Giovambattista Fazioli"
          AuthorURI = "http://undolog.com"
          Description = "WP Kirk is a WP Bones boilperate plugin"
          DomainPath = "localization"
          Name = "WP Kirk"
          Network = false
          PluginURI = "http://undolog.com"
          TextDomain = "wp-kirk"
          Title = "WP Kirk"
          Version = "1.0.0"

         */

        // plugin slug
        $this->slug = str_replace('-', '_', sanitize_title($this->pluginData['Name'])) . '_slug';

        // Load text domain
        load_plugin_textdomain("wp-kirk", false, trailingslashit(basename($this->basePath)) . $this->pluginData['DomainPath']);

        // Activation & Deactivation Hook
        register_activation_hook($this->file, [$this, 'activation']);
        register_deactivation_hook($this->file, [$this, 'deactivation']);

        /*
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

        // Fires after WordPress has finished loading but before any headers are sent.
        add_action('init', [$this, 'init']);

        // Fires before the administration menu loads in the admin.
        add_action('admin_menu', [$this, 'admin_menu']);

        // Fires after all default WordPress widgets have been registered.
        add_action('widgets_init', [$this, 'widgets_init']);

        // Filter a screen option value before it is set.
        add_filter('set-screen-option', [$this, 'set_screen_option'], 10, 3);

        static::$instance = $this;

        return $this;

    }

    protected function getOptionsAttribute()
    {
        if (is_null($this->_options)) {
            $this->_options = new WordPressOption($this);
        }

        return $this->_options;
    }

    protected function getRequestAttribute()
    {
        if (is_null($this->_request)) {
            $this->_request = new Request();
        }

        return $this->_request;
    }

    protected function getPluginBasenameAttribute()
    {
        return plugin_basename($this->file);
    }

    protected function getCssAttribute()
    {
        return "{$this->baseUri}/public/css";
    }

    protected function getJsAttribute()
    {
        return "{$this->baseUri}/public/js";
    }

    protected function getImagesAttribute()
    {
        return "{$this->baseUri}/public/images";
    }

    public function __get($name)
    {
        $method = 'get' . Str::studly($name) . 'Attribute';
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        if (in_array($name, array_keys($this->pluginData))) {
            return $this->pluginData[$name];
        }
    }

    public function set_screen_option($status, $option, $value)
    {
        if (in_array($option, array_keys($this->config('plugin.screen_options', [])))) {
            return $value;
        }

        return $status;
    }

    /**
     * Get / set the specified configuration value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param array|string $key
     * @param mixed        $default
     *
     * @return mixed
     */
    public function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return [];
        }

        $parts = explode('.', $key);

        $filename = "{$parts[ 0 ]}.php";
        $key      = isset($parts[1]) ? $parts[1] : null;

        $array = include "{$this->basePath}/config/{$filename}";

        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
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
     * Get the base path of the plugin installation.
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Return the absolute URL for the installation plugin.
     *
     * @return string
     */
    public function getBaseUri()
    {
        return $this->baseUri;
    }

    public function vendor($vendor = "wpbones")
    {
        return "{$this->baseUri}/vendor/{$vendor}";
    }

    /**
     * Gets the value of an environment variable. Supports boolean, empty and null.
     *
     * @param string $key
     * @param mixed  $default
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
     * @param null $key  Optional. Default null.
     * @param null $data Optional. Default null.
     *
     * @return \WPKirk\WPBones\View\View
     */
    public function view($key = null, $data = null)
    {

        $view = new View($this, $key, $data);

        return $view;

    }

    public function getPageUrl($pageSlug)
    {
        return add_query_arg(['page' => $pageSlug], admin_url('admin.php'));
    }

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
     * @param string|array $filename Filename
     * @param array        $deps     Optional. Dependences array
     * @param null         $version  Optional. Default plugin version
     */
    public function css($filename, $deps = [], $version = null)
    {
        $filenames = (array) $filename;

        foreach ($filenames as $file) {
            wp_enqueue_style($this->slug . Str::slug($file),
                $this->css . '/' . $file,
                (array) $deps,
                $version??$this->Version
            );
        }
    }

    /**
     * Helper method to load (enqueue) styles.
     *
     * For your convenience, the params $filename may be an array of file.
     *
     * @param string|array $filename Filenames
     * @param array        $deps     Optional. Dependences array
     * @param null         $version  Optional. Default plugin version
     * @param bool         $footer   Optional. Load on footer. Default true
     */
    public function js($filename, $deps = [], $version = null, $footer = true)
    {
        $filenames = (array) $filename;

        foreach ($filenames as $file) {
            wp_enqueue_script($this->slug . Str::slug($file),
                WPKirk()->js . '/' . $file,
                (array) $deps,
                $version??$this->Version,
                $footer
            );
        }
    }

    /**
     * Called when a plugin is activate; `register_activation_hook()`
     *
     */
    public function activation()
    {
        $this->options->delta();

        // include your own activation
        $activation = include_once "{$this->basePath}/plugin/activation.php";

        // migrations
        foreach (glob("{$this->basePath}/database/migrations/*.php") as $filename) {
            include $filename;
            foreach ($this->getFileClasses($filename) as $className) {
                $instance = new $className;
            }
        }

        // seeders
        foreach (glob("{$this->basePath}/database/seeds/*.php") as $filename) {
            include $filename;
            foreach ($this->getFileClasses($filename) as $className) {
                $instance = new $className;
            }
        }
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
     * Return the list of classes in a PHP file.
     *
     * @param string $filename A PHP Filename file.
     *
     * @return array|bool
     */
    private function getFileClasses($filename)
    {
        $code = file_get_contents($filename);

        if (empty($code)) {
            return false;
        }

        $classes = [];
        $tokens  = token_get_all($code);
        $count   = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS
                && $tokens[$i - 1][0] == T_WHITESPACE
                && $tokens[$i][0] == T_STRING
            ) {

                $class_name = $tokens[$i][1];
                $classes[]  = $class_name;
            }
        }

        return $classes;

    }

    /**
     * Called when a plugin is deactivate; `register_deactivation_hook()`
     *
     */
    public function deactivation()
    {
        $deactivation = include_once "{$this->basePath}/plugin/deactivation.php";
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
     */
    public function init()
    {
        $init = include "{$this->basePath}/config/plugin.php";

        if (is_array($init)) {

            // Here we are going to init Service Providers

            // Log
            $object = new LogServiceProvider($this);
            $object->register();
            $this->provides['Log'] = $object;

            // Custom post types Service Provider
            if (isset($init['custom_post_types']) && !empty($init['custom_post_types'])) {
                foreach ($init['custom_post_types'] as $className) {
                    $object = new $className($this);
                    $object->register();
                    $this->provides[$className] = $object;
                }
            }

            // Custom taxonomy type Service Provider
            if (isset($init['custom_taxonomy_types']) && !empty($init['custom_taxonomy_types'])) {
                foreach ($init['custom_taxonomy_types'] as $className) {
                    $object = new $className($this);
                    $object->register();
                    $this->provides[$className] = $object;
                }
            }

            // Shortcodes Service Provider
            if (isset($init['shortcodes']) && !empty($init['shortcodes'])) {
                foreach ($init['shortcodes'] as $className) {
                    $object = new $className($this);
                    $object->register();
                    $this->provides[$className] = $object;
                }
            }

            // Ajax Service Provider
            if ($this->isAjax()) {
                if (isset($init['ajax']) && !empty($init['ajax'])) {
                    foreach ($init['ajax'] as $className) {
                        $object = new $className($this);
                        $object->register();
                        $this->provides[$className] = $object;
                    }
                }
            }

            // Custom service provider
            if (isset($init['providers']) && !empty($init['providers'])) {
                foreach ($init['providers'] as $className) {
                    $object = new $className($this);
                    $object->register();
                    $this->provides[$className] = $object;
                }
            }
        }
    }

    /**
     * Return TRUE if an Ajax called
     *
     * @return bool
     */
    public function isAjax()
    {
        if (defined('DOING_AJAX')) {
            return true;
        }
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {
            return true;
        }

        return false;
    }

    public function log()
    {
        return $this->provides['Log'];
    }

    /**
     * Fires before the administration menu loads in the admin.
     */
    public function admin_menu()
    {
        global $admin_page_hooks, $_registered_pages, $_parent_pages;

        $menus = include_once "{$this->basePath}/config/menus.php";

        if (!empty($menus) && is_array($menus)) {

            foreach ($menus as $topLevelSlug => $menu) {

                // sanitize array keys
                $menu['position']   = isset($menu['position']) ? $menu['position'] : null;
                $menu['capability'] = isset($menu['capability']) ? $menu['capability'] : 'read';
                $menu['icon']       = isset($menu['icon']) ? $menu['icon'] : '';
                $menu['page_title'] = isset($menu['page_title']) ? $menu['page_title'] : $menu['menu_title'];

                // icon
                $icon = $menu['icon'];
                if (isset($menu['icon']) && !empty($menu['icon']) && 'dashicons' != substr($menu['icon'], 0, 9)) {
                    $icon = $this->images . '/' . $menu['icon'];
                }

                $firstMenu = true;

                if (substr($topLevelSlug, 0, 8) !== 'edit.php') {
                    add_menu_page($menu['page_title'], $menu['menu_title'], $menu['capability'], $topLevelSlug, '', $icon, $menu['position']);
                } else {
                    $firstMenu = false;
                }

                foreach ($menu['items'] as $key => $subMenu) {

                    if (is_null($subMenu)) {
                        continue;
                    }

                    // index 0
                    if (empty($key)) {
                        $key = '0';
                    }

                    // sanitize array keys
                    $subMenu['capability'] = isset($subMenu['capability']) ? $subMenu['capability'] : $menu['capability'];
                    $subMenu['page_title'] = isset($subMenu['page_title']) ? $subMenu['page_title'] : $subMenu['menu_title'];

                    // key could be a number
                    $key = str_replace('-', "_", sanitize_title($key));

                    $array     = explode('\\', __NAMESPACE__);
                    $namespace = sanitize_title($array[0]);

                    // submenu slug
                    $submenuSlug = "{$namespace}_{$key}";

                    if ($firstMenu) {
                        $firstMenu   = false;
                        $submenuSlug = $topLevelSlug;
                    }

                    // get hook
                    $hook = $this->getCallableHook($subMenu['route']);

                    $subMenuHook = add_submenu_page($topLevelSlug, $subMenu['page_title'], $subMenu['menu_title'], $subMenu['capability'], $submenuSlug, $hook);

                    if (isset($subMenu['route']['load'])) {
                        list($controller, $method) = explode('@', $subMenu['route']['load']);

                        add_action("load-{$subMenuHook}",
                            function () use ($controller, $method) {
                                $className = "WPKirk\\Http\\Controllers\\{$controller}";
                                $instance  = new $className;

                                return $instance->{$method}();
                            });
                    }

                    if (isset($subMenu['route']['resource'])) {
                        $controller = $subMenu['route']['resource'];

                        add_action("load-{$subMenuHook}",
                            function () use ($controller) {
                                $className = "WPKirk\\Http\\Controllers\\{$controller}";
                                $instance  = new $className;
                                if (method_exists($instance, 'load')) {
                                    return $instance->load();
                                }
                            });
                    }
                }
            }
        }

        // custom hidden pages
        $pages = include_once "{$this->basePath}/config/routes.php";

        if (!empty($pages) && is_array($pages)) {
            foreach ($pages as $pageSlug => $page) {

                $pageSlug                    = plugin_basename($pageSlug);
                $admin_page_hooks[$pageSlug] = !isset($page['title']) ?: $page['title'];
                $hookName                    = get_plugin_page_hookname($pageSlug, '');

                if (!empty($hookName)) {

                    if ($hook = $this->getCallableHook($page['route'])) {

                        add_action($hookName, $hook);

                        $_registered_pages[$hookName] = true;
                        $_parent_pages[$pageSlug]     = false;
                    }
                }
            }
        }
    }

    // -- private

    private function getCallableHook($routes)
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
            $method     = $methods[$verb];
        } // by single verb and controller@method
        else {

            if (isset($routes[$verb])) {
                list($controller, $method) = explode('@', $routes[$verb]);
            } // default "get"
            else {
                if (isset($routes['get'])) {
                    list($controller, $method) = explode('@', $routes['get']);
                }
            }
        }

        if (isset($controller) && isset($method)) {
            $hook = function () use ($controller, $method) {
                $className = "WPKirk\\Http\\Controllers\\{$controller}";
                $instance  = new $className;

                if (method_exists($instance, 'render')) {
                    return ($instance->render("{$method}"));
                }
            };

            return $hook;
        }

        return null;
    }

    public function widgets_init()
    {
        global $wp_widget_factory;

        $init = include "{$this->basePath}/config/plugin.php";

        if (isset($init['widgets']) && is_array($init['widgets']) && !empty($init['widgets'])) {
            foreach ($init['widgets'] as $className) {
                //register_widget( $className );
                $wp_widget_factory->widgets[$className] = new $className($this);
            }
        }
    }

    private function initEloquent()
    {

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ]);

        // Set the event dispatcher used by Eloquent models... (optional)
        // use Illuminate\Events\Dispatcher;
        // use Illuminate\Container\Container;
        // $capsule->setEventDispatcher(new Dispatcher(new Container));

        // Make this Capsule instance available globally via static methods... (optional)
        $capsule->setAsGlobal();

        // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
        $capsule->bootEloquent();
    }

}