#!/usr/bin/env php
<?php

define('WPBONES_MINIMAL_PHP_VERSION', "7.1");

if (version_compare(PHP_VERSION, WPBONES_MINIMAL_PHP_VERSION) < 0) {
    echo "\n\033[33;5;82mWarning!!\n";
    echo "\n\033[38;5;82m\t" . 'You must run with PHP version ' . WPBONES_MINIMAL_PHP_VERSION . ' or greather';
    echo "\033[0m\n\n";
    exit;
}

/**
 * @class BonesCommandLine
 */
class BonesCommandLine
{

    /**
     * WP Bones version
     */
    const VERSION = '0.9.51';

    /**
     * Plugin name.
     *
     * @var string
     */
    protected $plginName;

    /**
     * Plugin namespace.
     *
     * @var string
     */
    protected $namespace;

    /**
     * Used for additional kernel command.
     *
     * @var null
     */
    protected $kernel = null;

    /**
     * List of files and folders to skip during the deploy.
     *
     * @var array
     */
    protected $skipWhenDeploy = [];

    /**
     * Base folder during the deploy.
     *
     * @var string
     */
    protected $rootDeploy = '';

    public function __construct()
    {
        list($pluginName, $namespace) = explode(",", file_get_contents('namespace'));

        $this->plginName = $pluginName;
        $this->namespace = $namespace;

        $this->boot();
    }

    /**
     * Load WordPress core and all environment.
     *
     */
    protected function loadWordPress()
    {
        /*
        |--------------------------------------------------------------------------
        | Load WordPress
        |--------------------------------------------------------------------------
        |
        | We have to load the WordPress environment.
        |
        */
        if (!file_exists(__DIR__ . '/../../../wp-load.php')) {
            echo "\n\033[33;5;82mWarning!!\n";
            echo "\n\033[38;5;82m\t" . 'You must be inside "wp-content/plugins/" folders';
            echo "\033[0m\n\n";
            exit;
        }

        require __DIR__ . '/../../../wp-load.php';

        /*
        |--------------------------------------------------------------------------
        | Register The Auto Loader
        |--------------------------------------------------------------------------
        |
        | Composer provides a convenient, automatically generated class loader
        | for our application. We just need to utilize it! We'll require it
        | into the script here so that we do not have to worry about the
        | loading of any our classes "manually". Feels great to relax.
        |
        */

        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require __DIR__ . '/vendor/autoload.php';
        }

        /*
        |--------------------------------------------------------------------------
        | Load this plugin env
        |--------------------------------------------------------------------------
        |
        */

        if (file_exists(__DIR__ . '/bootstrap/plugin.php')) {
            $plugin = require_once __DIR__ . '/bootstrap/plugin.php';
        }
    }

    /**
     * Check and load for console kernel extensions.
     *
     */
    protected function loadKernel()
    {
        $kernelClass        = "{$this->namespace}\\Console\\Kernel";
        $WPBoneskernelClass = "{$this->namespace}\\WPBones\\Foundation\\Console\\Kernel";

        if (class_exists($WPBoneskernelClass) && class_exists($kernelClass)) {
            $this->kernel = new $kernelClass;
        }
    }

    /**
     * Return TRUE if the command is in console argument.
     *
     * @param string $command Bones command to check.
     * @return bool
     */
    protected function command($command)
    {
        $arguments = $this->arguments();

        return ($command === ($arguments[0]??""));
    }

    /**
     * Return the arguments after "php bones".
     *
     * @param int $index Optional. Index of argument.
     *                   If NULL will be returned the whole array.
     * @return mixed|array
     */
    protected function arguments($index = null)
    {
        $argv = $_SERVER['argv'];

        // strip the application name
        array_shift($argv);

        return $index ? ($argv[$index]??null) : $argv;
    }

    protected function isHelp()
    {
        $arguments = $this->arguments();

        return (empty($arguments) || $this->command('--help'));
    }

    /**
     * This is a special bootstrap in order to avoid the WordPress and kernel envirnment
     * when we have to rename the plugin and vendor structure.
     *
     */
    public function boot()
    {
        if ($this->command('rename')) {
            $this->rename($this->arguments(1));
        } else {
            $this->loadWordPress();

            $this->loadKernel();

            $this->handle();
        }
    }

    /**
     * Let's roll
     *
     * @return \BonesCommandLine
     */
    public static function run()
    {
        $instance = new self;

        return $instance;
    }

    /*
    |--------------------------------------------------------------------------
    | Internal useful function
    |--------------------------------------------------------------------------
    |
    | Here you will find all internal methods
    |
    */

    protected function help()
    {
        echo '
  o       o o--o      o--o
  |       | |   |     |   |
  o   o   o O--o      O--o  o-o o-o  o-o o-o
   \ / \ /  |         |   | | | |  | |-\'  \
    o   o   o         o--o  o-o o  o o-o o-o

    ';

        $this->info("\nBones Version " . self::VERSION . "\n");
        $this->info("Usage:\n");
        $this->line(" command [options] [arguments]");
        $this->info("\nAvailable commands:");
        $this->line(" deploy                  Create a deploy version");
        $this->line(" install                 Install a new WP Bones plugin");
        $this->line(" optimize                Run composer dump-autoload with -o option");
        $this->line(" rename                  Set or change the plugin name");
        $this->line(" require                 Install a WP Bones package");
        $this->line(" tinker                  Interact with your application");
        $this->line(" update                  Update the Framework");
        $this->info("migrate");
        $this->line(" migrate:create          Create a new Migration");
        $this->info("make");
        $this->line(" make:ajax               Create a new Ajax service provider class");
        $this->line(" make:controller         Create a new controller class");
        $this->line(" make:console            Create a new Bones command");
        $this->line(" make:cpt                Create a new Custom Post Type service provider class");
        $this->line(" make:ctt                Create a new Custom Taxonomy Type service provider class");
        $this->line(" make:shortcode          Create a new Shortcode service provider class");
        $this->line(" make:provider           Create a new service provider class");
        $this->line(" make:widget             Create a new Widget service provider class");
        $this->line(" make:model              Create a new database model class");

        if ($this->kernel && $this->kernel->hasCommands()) {
            $this->info("Extensions");
            $this->kernel->displayHelp();
        }

        echo "\n\n";
    }

    protected function line($str)
    {
        echo "\033[38;5;82m" . $str;
        echo "\033[0m\n";
    }

    protected function info($str)
    {
        echo "\033[38;5;213m" . $str;
        echo "\033[0m\n";
    }

    protected function ask($str, $default = '')
    {
        echo "\n\e[38;5;88m$str" . (empty($default) ? "" : " (default: {$default})") . "\e[0m ";

        $handle = fopen("php://stdin", "r");
        $line   = fgets($handle);

        fclose($handle);

        $line = trim($line, " \n\r");

        return $line ?: $default;
    }

    /**
     * Return an array with all matched files from root folder. This method release the follow filters:
     *
     *     wpdk_rglob_find_dir( true, $file ) - when find a dir
     *     wpdk_rglob_find_file( true, $file ) - when find a a file
     *     wpdk_rglob_matched( $regexp_result, $file, $match ) - after preg_match() done
     *
     * @brief get all matched files
     * @since 1.0.0.b4
     *
     * @param string $path    Folder root
     * @param string $match   Optional. Regex to apply on file name. For example use '/^.*\.(php)$/i' to get only php
     *                        file. Default is empty
     *
     * @return array
     */
    protected function recursiveScan($path, $match = '')
    {
        /**
         * Return an array with all matched files from root folder.
         *
         * @brief get all matched files
         * @note  Internal recursive use only
         *
         * @param string  $path   Folder root
         * @param string  $match  Optional. Regex to apply on file name. For example use '/^.*\.(php)$/i' to get only php file
         * @param array  &$result Optional. Result array. Empty form first call
         *
         * @return array
         */
        function _rglob($path, $match = '', &$result = [])
        {
            $path = rtrim($path, '/\\') . '/';

            $files = glob($path . '*', GLOB_MARK);
            if (false !== $files) {
                foreach ($files as $file) {
                    if (is_dir($file)) {
                        $continue = true;
                        if ($continue) {
                            _rglob($file, $match, $result);
                        }
                    } elseif (!empty($match)) {
                        $continue = true;
                        if (false == $continue) {
                            break;
                        }
                        $regexp_result = [];
                        $error         = preg_match($match, $file, $regexp_result);
                        if (0 !== $error || false !== $error) {
                            $regexp_result = true;
                            if (!empty($regexp_result)) {
                                $result[] = $regexp_result[0];
                            }
                        }
                    } else {
                        $result[] = $file;
                    }
                }

                return $result;
            }
        }

        $result = [];

        return _rglob($path, $match, $result);
    }

    /*
    |--------------------------------------------------------------------------
    | Public task
    |--------------------------------------------------------------------------
    |
    | Here you will find all tasks that a user can run from console.
    |
    */

    protected function sanitize($title)
    {
        $title = strip_tags($title);
        // Preserve escaped octets.
        $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
        // Remove percent signs that are not part of an octet.
        $title = str_replace('%', '', $title);
        // Restore octets.
        $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);

        $title = strtolower($title);

        $title = preg_replace('/&.+?;/', '', $title); // kill entities
        $title = str_replace('.', '-', $title);

        $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
        $title = preg_replace('/\s+/', '-', $title);
        $title = preg_replace('|-+|', '-', $title);
        $title = trim($title, '-');

        return $title;
    }

    protected function rename($pluginName = '')
    {
        if (empty($pluginName)) {
            // previous namespace
            list($pluginName, $previousNamespace) = explode(",", file_get_contents('namespace'));

            // sanitize namespace
            $namespace          = str_replace(" ", "", $pluginName);
            $previousPluginName = 'WP Kirk';
            $previousNamespace  = 'WPKirk';
        } else {
            // sanitize namespace
            $namespace = str_replace(' ', '', $pluginName);

            // previous namespace
            list($previousPluginName, $previousNamespace) = explode(",", file_get_contents('namespace'));

            // check the same?
            if ($previousNamespace == $namespace) {
                $this->info("\nThe new namespace is equal. Nothing to change\n");
                exit(0);
            }
        }

        // previous slug
        $previousSlug = str_replace("-", "_", $this->sanitize($previousPluginName)) . "_slug";

        // slug
        $slug = str_replace("-", "_", $this->sanitize($pluginName)) . "_slug";

        // previous vars
        $previousVars = str_replace("-", "_", $this->sanitize($previousPluginName)) . "_vars";

        // vars - used in custom post types service provider
        $vars = str_replace("-", "_", $this->sanitize($pluginName)) . "_vars";

        // previous css id
        $previousCssId = $this->sanitize($previousPluginName);

        // current css id
        $currentCssId = $this->sanitize($pluginName);

        // remove all composer
        $files = array_filter(array_map(function ($e) {
            if (false !== strpos($e, "node_modules") ||
                false !== strpos($e, "vendor/wpbones/wpbones/src/Console/bin/bones")) {
                return false;
            }

            return $e;
        }, $this->recursiveScan("*")));

        // merge
        $files = array_merge($files, [
            'index.php',
            'composer.json',
            'readme.txt',
        ]);

        // change namespace
        foreach ($files as $file) {
            $this->line("Loading and process {$file}...");

            $content = file_get_contents($file);
            $content = str_replace($previousNamespace, $namespace, $content);
            $content = str_replace(strtoupper($previousNamespace), strtoupper($namespace), $content);
            $content = str_replace($previousSlug, $slug, $content);
            $content = str_replace($previousVars, $vars, $content);
            $content = str_replace($previousPluginName, $pluginName, $content);
            $content = str_replace($previousCssId, $currentCssId, $content);
            file_put_contents($file, $content);
        }

        foreach (glob('localization/*') as $file) {
            $newFile = str_replace($previousCssId, $currentCssId, $file);
            rename($file, $newFile);
        }

        foreach (glob('resources/assets/js/*') as $file) {
            $newFile = str_replace($previousCssId, $currentCssId, $file);
            rename($file, $newFile);
        }

        foreach (glob('resources/assets/less/*') as $file) {
            $newFile = str_replace($previousCssId, $currentCssId, $file);
            rename($file, $newFile);
        }

        // save new namespace
        file_put_contents('namespace', "{$pluginName},{$namespace}");
    }

    protected function install($argv)
    {
        // TODO check if the first time or if is time to install

        if (!isset($argv[1]) || empty($argv[1])) {
            $name = $this->ask('Enter name of your plugin:');
        } elseif (isset($argv[1]) && "--help" === $argv[1]) {
            $this->line("\nUsage:");
            $this->info("  install <plugin name>\n");
            $this->line("Arguments:");
            $this->info("  plugin name\tThe name of plugin. Add quotes if the plugin name containes spaces.");
            exit(0);
        } else {
            $name = $argv[1];
        }

        $this->update();
    }

    protected function update()
    {
        // we have to remove previous version
        //$res = `rm -rf vendor/`;
        //$this->deleteDirectory('vendor');

        // update composer module
        $res = `composer update`;

        // rename as is it and execute composer
        $this->rename();
    }

    protected function deploy($path)
    {
        $this->info("\nStarting deploy ¯\_(ツ)_/¯\n");

        $path = rtrim($path, '/');

        if (empty($path)) {
            $path = $this->ask('Enter the complete path of deploy:');
        } elseif ("--help" === $path) {
            $this->line("\nUsage:");
            $this->info("  deploy <path>\n");
            $this->line("Arguments:");
            $this->info("  path\tThe complete path of deploy.");
            exit(0);
        }

        if (!empty($path)) {
            // run yarn production
            $this->info('🕐 Build for production');
            shell_exec('gulp production');
            $this->info("\e[1A👍");

            // alternative method to customize the deploy
            @include 'deploy.php';

            // files and folders to skip
            $this->skipWhenDeploy = [
                '/node_modules',
                '/.git',
                '/.gitignore',
                '/.DS_Store',
                '/.babelrc',
                '/bones',
                '/resources/assets',
                '/deploy.php',
                '/composer.json',
                '/composer.lock',
                '/namespace',
                '/gulpfile.js',
                '/package.json',
                '/package-lock.json',
                '/yarn.lock',
                '/README.md',
                '/webpack.mix.js',
                '/webpack.config.js',
                '/phpcs.xml.dist',
                '/mix-manifest.json',
            ];

            /**
             * Filter the list of files and folders to skip during the deploy.
             *
             * @param array $array The files and folders are relative to the root of plugin.
             */
            $this->skipWhenDeploy = apply_filters('wpbones_console_deploy_skip', $this->skipWhenDeploy);

            $this->rootDeploy = __DIR__;

            $this->info("🕐 Copying to {$path}");
            $this->xcopy(__DIR__, $path);
            $this->info("\e[1A👍");

            /**
             * Fires when the console deploy is completed.
             *
             * @param mixed  $bones This bones command instance.
             * @param string $path  The deployed path.
             */
            do_action('wpbones_console_deployed', $this, $path);

            $this->info("👏 Deploy Completed!");
        }
    }

    protected function optimize()
    {
        $this->line(`composer dump-autoload -o`);
    }

    protected function requirePackage($package)
    {
        // help
        if (empty($package) || $package == '--help') {
            $this->info('Use php bones require <PackageName>');

            return;
        }

        $this->line(`composer require {$package}`);

        // rename as is it and execute composer
        $this->rename();
    }

    protected function tinker()
    {
        $eval = $this->ask(">>>");

        try {
            if ($eval == 'exit') {
                exit;
            }

            if (substr($eval, -1) != ';') {
                $eval .= ';';
            }

            $this->line(eval($eval));
        } catch (\Exception $e) {
            $this->info(eval($e->getMessage()));
        } finally {
            $this->tinker();
        }
    }

    public function deleteDirectory($path)
    {
        $path = rtrim($path, '/');

        array_map(function ($file) {

            if (is_dir($file)) {
                $this->deleteDirectory($file);
            } else {
                @unlink($file);
            }

        }, glob("{$path}/" . '{,.}[!.,!..]*', GLOB_MARK | GLOB_BRACE));

        @rmdir("{$path}");
    }

    protected function xcopy($source, $dest, $permissions = 0755)
    {
        // Check for symlinks
        if (is_link($source)) {
            return symlink(readlink($source), $dest);
        }

        // Simple copy for a file
        if (is_file($source)) {
            return copy($source, $dest);
        }

        // Make destination directory
        if (!is_dir($dest)) {
            mkdir($dest, $permissions);
        }

        // Loop through the folder
        $dir = dir($source);

        while (false !== $entry = $dir->read()) {

            // files and folder to skip
            if ($entry === '.' || $entry === '..' || $this->skip("{$source}/{$entry}")) {
                continue;
            }

            // Deep copy directories
            $this->xcopy("{$source}/{$entry}", "{$dest}/{$entry}", $permissions);
        }

        // Clean up
        $dir->close();

        return true;
    }

    protected function skip($value)
    {
        $single = str_replace($this->rootDeploy, '', $value);

        return in_array($single, $this->skipWhenDeploy);
    }

    protected function createMigrate($tablename)
    {
        // help
        if (empty($tablename) || $tablename == '--help') {
            $this->info('Use php bones migrate:make <Tablename>');

            return;
        }

        $filename = sprintf(
            '%s_create_%s_table.php',
            date('Y_m_d_His'),
            strtolower($tablename)
        );

        // previous namespace
        list($pluginName, $namespace) = explode(",", file_get_contents('namespace'));

        // get the stub
        $content = file_get_contents("vendor/wpbones/wpbones/src/Console/stubs/migrate.stub");
        $content = str_replace('{Namespace}', $namespace, $content);
        $content = str_replace('{Tablename}', $tablename, $content);

        file_put_contents("database/migrations/{$filename}", $content);

        $this->line(" Created database/migrations/{$filename}");
    }

    protected function createCustomPostType($className)
    {
        // help
        if (empty($className) || $className == '--help') {
            $this->info('Use php bones make:cpt <ClassName>');

            return;
        }

        $filename = sprintf('%s.php', $className);

        // previous namespace
        list($pluginName, $namespace) = explode(",", file_get_contents('namespace'));

        $slug = str_replace("-", "_", $this->sanitize($pluginName));

        $id     = $this->ask("Enter a ID:", $slug);
        $name   = $this->ask("Enter the name:");
        $plural = $this->ask("Enter the plural name:");

        if (empty($id)) {
            $id = $slug;
        }

        // get the stub
        $content = file_get_contents("vendor/wpbones/wpbones/src/Console/stubs/cpt.stub");
        $content = str_replace('{Namespace}', $namespace, $content);
        $content = str_replace('{ClassName}', $className, $content);
        $content = str_replace('{ID}', $id, $content);
        $content = str_replace('{Name}', $name, $content);
        $content = str_replace('{Plural}', $plural, $content);

        if (!is_dir('plugin/CustomPostTypes')) {
            mkdir("plugin/CustomPostTypes", 0777, true);
        }

        file_put_contents("plugin/CustomPostTypes/{$filename}", $content);

        $this->line(" Created plugin/CustomPostTypes/{$filename}");

        $this->info("Remember to add {$className} in the config/plugin.php array in the 'custom_post_types' key.");
    }

    protected function createCustomTaxonomyType($className)
    {
        // help
        if (empty($className) || $className == '--help') {
            $this->info('Use php bones make:ctt <ClassName>');

            return;
        }

        $filename = sprintf('%s.php', $className);

        // previous namespace
        list($pluginName, $namespace) = explode(",", file_get_contents('namespace'));

        $slug = str_replace("-", "_", $this->sanitize($pluginName));

        $id     = $this->ask("Enter a ID:", $slug);
        $name   = $this->ask("Enter the name:");
        $plural = $this->ask("Enter the plural name:");

        $this->line('The object type below refers to the id of your previous Custom Post Type');

        $objectType = $this->ask("Enter the object type to bound:");

        if (empty($id)) {
            $id = $slug;
        }

        // get the stub
        $content = file_get_contents("vendor/wpbones/wpbones/src/Console/stubs/ctt.stub");
        $content = str_replace('{Namespace}', $namespace, $content);
        $content = str_replace('{ClassName}', $className, $content);
        $content = str_replace('{ID}', $id, $content);
        $content = str_replace('{Name}', $name, $content);
        $content = str_replace('{Plural}', $plural, $content);
        $content = str_replace('{ObjectType}', $objectType, $content);

        if (!is_dir('plugin/CustomTaxonomyTypes')) {
            mkdir("plugin/CustomTaxonomyTypes", 0777, true);
        }

        file_put_contents("plugin/CustomTaxonomyTypes/{$filename}", $content);

        $this->line(" Created plugin/CustomTaxonomyTypes/{$filename}");

        $this->info("Remember to add {$className} in the config/plugin.php array in the 'custom_taxonomy_types' key.");
    }

    protected function createController($className)
    {
        // help
        if (empty($className) || $className == '--help') {
            $this->info('Use php bones make:controller <ClassName>');

            return;
        }

        // previous namespace
        list($pluginName, $namespace) = explode(",", file_get_contents('namespace'));

        // get additional path
        $path = $namespacePath = '';
        if (false !== strpos($className, '/')) {
            $parts         = explode('/', $className);
            $className     = array_pop($parts);
            $path          = implode('/', $parts) . '/';
            $namespacePath = '\\' . implode('\\', $parts);
        }

        // get the stub
        $content = file_get_contents("vendor/wpbones/wpbones/src/Console/stubs/controller.stub");
        $content = str_replace('{Namespace}', $namespace, $content);
        $content = str_replace('{ClassName}', $className, $content);

        if (!empty($path)) {
            $content = str_replace('{Path}', $namespacePath, $content);
            mkdir("plugin/Http/Controllers/{$path}", 0777, true);
        } else {
            $content = str_replace('{Path}', '', $content);
        }

        $filename = sprintf('%s.php', $className);

        file_put_contents("plugin/Http/Controllers/{$path}{$filename}", $content);

        $this->line(" Created plugin/Http/Controllers/{$path}{$filename}");

        $this->optimize();
    }

    protected function createCommand($className)
    {
        // help
        if (empty($className) || $className == '--help') {
            $this->info('Use php bones make:console <ClassName>');

            return;
        }

        $filename = sprintf('%s.php', $className);

        // previous namespace
        list($pluginName, $namespace) = explode(",", file_get_contents('namespace'));

        $signature = str_replace("-", "", $this->sanitize($pluginName));
        $command   = str_replace("-", "", $this->sanitize($className));

        $signature = $this->ask("Enter a signature:", $signature);
        $command   = $this->ask("Enter the command:", $command);

        // get the stub
        $content = file_get_contents("vendor/wpbones/wpbones/src/Console/stubs/command.stub");
        $content = str_replace('{Namespace}', $namespace, $content);
        $content = str_replace('{ClassName}', $className, $content);
        $content = str_replace('{Signature}', $signature, $content);
        $content = str_replace('{CommandName}', $command, $content);

        if (!is_dir('plugin/Console/Commands')) {
            mkdir("plugin/Console/Commands", 0777, true);
        }

        file_put_contents("plugin/Console/Commands/{$filename}", $content);

        $this->line(" Created plugin/Console/Commands/{$filename}");

        // check if plugin/Console/Kernel.php already exists
        if (file_exists('plugin/Console/Kernel.php')) {
            $this->info("Remember to add {$className} in the plugin/Console/Commands/Kernel.php property array \$commands");
        } else {
            // get the stub
            $content = file_get_contents("vendor/wpbones/wpbones/src/Console/stubs/kernel.stub");
            $content = str_replace('{Namespace}', $namespace, $content);
            $content = str_replace('{ClassName}', $className, $content);
            file_put_contents("plugin/Console/Kernel.php", $content);

            $this->line(" Created plugin/Console/Kernel.php");
        }
    }

    protected function createShortcode($className)
    {
        // help
        if (empty($className) || $className == '--help') {
            $this->info('Use php bones make:shortcode <ClassName>');

            return;
        }

        $filename = sprintf('%s.php', $className);

        // previous namespace
        list($pluginName, $namespace) = explode(",", file_get_contents('namespace'));

        // get the stub
        $content = file_get_contents("vendor/wpbones/wpbones/src/Console/stubs/shortcode.stub");
        $content = str_replace('{Namespace}', $namespace, $content);
        $content = str_replace('{ClassName}', $className, $content);

        if (!is_dir('plugin/Shortcodes')) {
            mkdir("plugin/Shortcodes", 0777, true);
        }

        file_put_contents("plugin/Shortcodes/{$filename}", $content);

        $this->line(" Created plugin/Shortcodes/{$filename}");

        $this->info("Remember to add {$className} in the config/plugin.php array in the 'shortcodes' key.");
    }

    protected function createProvider($className)
    {
        // help
        if (empty($className) || $className == '--help') {
            $this->info('Use php bones make:provider <ClassName>');

            return;
        }

        $filename = sprintf('%s.php', $className);

        // previous namespace
        list($pluginName, $namespace) = explode(",", file_get_contents('namespace'));

        // get the stub
        $content = file_get_contents("vendor/wpbones/wpbones/src/Console/stubs/provider.stub");
        $content = str_replace('{Namespace}', $namespace, $content);
        $content = str_replace('{ClassName}', $className, $content);

        if (!is_dir('plugin/Providers')) {
            mkdir("plugin/Providers", 0777, true);
        }

        file_put_contents("plugin/Providers/{$filename}", $content);

        $this->line(" Created plugin/Providers/{$filename}");
    }

    protected function createWidget($className)
    {
        // help
        if (empty($className) || $className == '--help') {
            $this->info('Use php bones make:widget <ClassName>');

            return;
        }

        $filename = sprintf('%s.php', $className);

        // previous namespace
        list($pluginName, $namespace) = explode(",", file_get_contents('namespace'));

        $slug = str_replace("-", "_", $this->sanitize($className));

        // get the stub
        $content = file_get_contents("vendor/wpbones/wpbones/src/Console/stubs/widget.stub");
        $content = str_replace('{Namespace}', $namespace, $content);
        $content = str_replace('{ClassName}', $className, $content);
        $content = str_replace('{PluginName}', $pluginName, $content);
        $content = str_replace('{Slug}', $slug, $content);

        if (!is_dir('plugin/Widgets')) {
            mkdir("plugin/Widgets", 0777, true);
        }

        file_put_contents("plugin/Widgets/{$filename}", $content);

        $this->line(" Created plugin/Widgets/{$filename}");

        if (!is_dir('resources/views/widgets')) {
            mkdir("resources/views/widgets", 0777, true);
        }

        file_put_contents("resources/views/widgets/{$slug}-form.php", '<h2>Backend form</h2>');
        file_put_contents("resources/views/widgets/{$slug}-index.php", '<h2>Frontend Widget output</h2>');

        $this->line(" Created resources/views/widgets/{$slug}-form.php");
        $this->line(" Created resources/views/widgets/{$slug}-index.php");

        $this->info("Remember to add {$className} in the config/plugin.php array in the 'widgets' key.");
    }

    protected function createAjax($className)
    {
        // help
        if (empty($className) || $className == '--help') {
            $this->info('Use php bones make:ajax <ClassName>');

            return;
        }

        // previous namespace
        list($pluginName, $namespace) = explode(",", file_get_contents('namespace'));

        // get the stub
        $content = file_get_contents("vendor/wpbones/wpbones/src/Console/stubs/ajax.stub");
        $content = str_replace('{Namespace}', $namespace, $content);
        $content = str_replace('{ClassName}', $className, $content);

        if (!is_dir('plugin/Ajax')) {
            mkdir("plugin/Ajax", 0777, true);
        }

        $filename = sprintf('%s.php', $className);

        file_put_contents("plugin/Ajax/{$filename}", $content);

        $this->line(" Created plugin/Ajax/{$filename}");

        $this->info("Remember to add {$className} in the config/plugin.php array in the 'ajax' key.");
    }

    protected function createModel($className)
    {
        // help
        if (empty($className) || $className == '--help') {
            $this->info('Use php bones make:model <ClassName>');

            return;
        }

        // previous namespace
        list($pluginName, $namespace) = explode(",", file_get_contents('namespace'));

        // get additional path
        $path = $namespacePath = '';
        if (false !== strpos($className, '/')) {
            $parts         = explode('/', $className);
            $className     = array_pop($parts);
            $path          = implode('/', $parts) . '/';
            $namespacePath = '\\' . implode('\\', $parts);
        }

        // create the table
        $table = strtolower($className) . 's';

        // get the stub
        $content = file_get_contents("vendor/wpbones/wpbones/src/Console/stubs/model.stub");
        $content = str_replace('{Namespace}', $namespace, $content);
        $content = str_replace('{ClassName}', $className, $content);
        $content = str_replace('{Table}', $table, $content);

        if (!empty($path)) {
            $content = str_replace('{Path}', $namespacePath, $content);
            mkdir("plugin/Http/Controllers/{$path}", 0777, true);
        } else {
            $content = str_replace('{Path}', '', $content);
        }

        $filename = sprintf('%s.php', $className);

        file_put_contents("plugin/Http/Controllers/{$path}{$filename}", $content);

        $this->line(" Created plugin/Http/Controllers/{$path}{$filename}");

        $this->optimize();

    }

    /**
     * Run subtask.
     * Check argv from console and execute a task.
     *
     */
    protected function handle()
    {
        $argv = $_SERVER['argv'];

        // strip the application name
        array_shift($argv);

        if ($this->isHelp()) {
            $this->help();
        } // install
        elseif ($this->command('install')) {
            $this->install($this->arguments());
        } // Update
        elseif ($this->command('update')) {
            $this->update();
        } // Deploy
        elseif ($this->command('deploy')) {
            $this->deploy($this->arguments(1));
        } // Optimize
        elseif ($this->command('optimize')) {
            $this->optimize();
        } // Tinker
        elseif ($this->command('tinker')) {
            $this->tinker();
        } // Require
        elseif ($this->command('require')) {
            $this->requirePackage($this->arguments(1));
        } // migrate:create {table_name}
        elseif ($this->command('migrate:create')) {
            $this->createMigrate($this->arguments(1));
        } // make:controller {controller_name}
        elseif ($this->command('make:controller')) {
            $this->createController($this->arguments(1));
        } // make:console {command_name}
        elseif ($this->command('make:console')) {
            $this->createCommand($this->arguments(1));
        } // make:cpy {className}
        elseif ($this->command('make:cpt')) {
            $this->createCustomPostType($this->arguments(1));
        } // make:shortocde {className}
        elseif ($this->command('make:shortcode')) {
            $this->createShortcode($this->arguments(1));
        } // make:provider {className}
        elseif ($this->command('make:provider')) {
            $this->createProvider($this->arguments(1));
        } // make:ajax {className}
        elseif ($this->command('make:ajax')) {
            $this->createAjax($this->arguments(1));
        } // make:ctt {className}
        elseif ($this->command('make:ctt')) {
            $this->createCustomTaxonomyType($this->arguments(1));
        } // make:widget {className}
        elseif ($this->command('make:widget')) {
            $this->createWidget($this->arguments(1));
        } elseif ($this->command('make:model')) {
            $this->createModel($this->arguments(1));
        } else {
            $extended = false;

            if ($this->kernel) {
                $extended = $this->kernel->handle($this->arguments());
            }

            if (!$extended) {
                $this->info("\nUnknown command! Use --help for commands list\n");
            }
        }
    }
}

BonesCommandLine::run();
