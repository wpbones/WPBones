#!/usr/bin/env php
<?php

/*
|--------------------------------------------------------------------------
| SemVer
|--------------------------------------------------------------------------
|
| https://github.com/PHLAK/SemVer
|
*/

namespace Bones\SemVer\Exceptions {

    use Exception;

    class InvalidVersionException extends Exception
    {
    }
}

namespace Bones\SemVer\Traits {

    use Bones\SemVer\Version;

    trait Comparable
    {
        /**
         * Compare two versions. Returns -1, 0 or 1 if the first version is less
         * than, equal to or greater than the second version respectively.
         *
         * @param Version $version1 An instance of SemVer/Version
         * @param Version $version2 An instance of SemVer/Version
         */
        public static function compare(Version $version1, Version $version2): int
        {
            $v1 = [$version1->major, $version1->minor, $version1->patch];
            $v2 = [$version2->major, $version2->minor, $version2->patch];

            $baseComparison = $v1 <=> $v2;

            if ($baseComparison !== 0) {
                return $baseComparison;
            }

            if ($version1->preRelease !== null && $version2->preRelease === null) {
                return -1;
            }

            if ($version1->preRelease === null && $version2->preRelease !== null) {
                return 1;
            }

            $v1preReleaseParts = explode('.', $version1->preRelease ?? '');
            $v2preReleaseParts = explode('.', $version2->preRelease ?? '');

            $preReleases1 = array_pad($v1preReleaseParts, count($v2preReleaseParts), null);
            $preReleases2 = array_pad($v2preReleaseParts, count($v1preReleaseParts), null);

            return $preReleases1 <=> $preReleases2;
        }

        /**
         * Check if this Version object is greater than another.
         *
         * @param Version $version An instance of SemVer/Version
         *
         * @return bool True if this Version object is greater than the comparing
         *              object, otherwise false
         */
        public function gt(Version $version): bool
        {
            return self::compare($this, $version) > 0;
        }

        /**
         * Check if this Version object is less than another.
         *
         * @param Version $version An instance of SemVer/Version
         *
         * @return bool True if this Version object is less than the comparing
         *              object, otherwise false
         */
        public function lt(Version $version): bool
        {
            return self::compare($this, $version) < 0;
        }

        /**
         * Check if this Version object is equal to than another.
         *
         * @param Version $version An instance of SemVer/Version
         *
         * @return bool True if this Version object is equal to the comparing
         *              object, otherwise false
         */
        public function eq(Version $version): bool
        {
            return self::compare($this, $version) === 0;
        }

        /**
         * Check if this Version object is not equal to another.
         *
         * @param Version $version An instance of SemVer/Version
         *
         * @return bool True if this Version object is not equal to the comparing
         *              object, otherwise false
         */
        public function neq(Version $version): bool
        {
            return self::compare($this, $version) !== 0;
        }

        /**
         * Check if this Version object is greater than or equal to another.
         *
         * @param Version $version An instance of SemVer/Version
         *
         * @return bool True if this Version object is greater than or equal to the
         *              comparing object, otherwise false
         */
        public function gte(Version $version): bool
        {
            return self::compare($this, $version) >= 0;
        }

        /**
         * Check if this Version object is less than or equal to another.
         *
         * @param Version $version An instance of SemVer/Version
         *
         * @return bool True if this Version object is less than or equal to the
         *              comparing object, otherwise false
         */
        public function lte(Version $version): bool
        {
            return self::compare($this, $version) <= 0;
        }
    }


    trait Incrementable
    {
        /**
         * Increment the major version value by one.
         *
         * @return self This Version object
         */
        public function incrementMajor(): self
        {
            $this->setMajor($this->major + 1);

            return $this;
        }

        /**
         * Increment the minor version value by one.
         *
         * @return self This Version object
         */
        public function incrementMinor(): self
        {
            $this->setMinor($this->minor + 1);

            return $this;
        }

        /**
         * Increment the patch version value by one.
         *
         * @return self This Version object
         */
        public function incrementPatch(): self
        {
            $this->setPatch($this->patch + 1);

            return $this;
        }
    }
}

namespace Bones\SemVer {

    use Bones\SemVer\Exceptions\InvalidVersionException;
    use Bones\SemVer\Traits\Comparable;
    use Bones\SemVer\Traits\Incrementable;

    /**
     * @property int $major Major release number
     * @property int $minor Minor release number
     * @property int $patch Patch release number
     * @property string|null $preRelease Pre-release value
     * @property string|null $build Build release value
     */
    class Version
    {
        use Comparable;
        use Incrementable;

        /** @var int Major release number */
        protected $major;

        /** @var int Minor release number */
        protected $minor;

        /** @var int Patch release number */
        protected $patch;

        /** @var string|null Pre-release value */
        protected $preRelease;

        /** @var string|null Build release value */
        protected $build;

        /**
         * Class constructor, runs on object creation.
         *
         * @param string $version Version string
         *
         * @throws \Bones\SemVer\Exceptions\InvalidVersionException
         */
        public function __construct(string $version = '0.1.0')
        {
            $this->setVersion($version);
        }

        /**
         * Magic get method; provides access to version properties.
         *
         * @param string $property Version property
         *
         * @return mixed Version property value
         */
        public function __get(string $property)
        {
            return $this->$property;
        }

        /**
         * Magic toString method; allows object interaction as if it were a string.
         *
         * @return string Current version string
         */
        public function __toString(): string
        {
            $version = implode('.', [$this->major, $this->minor, $this->patch]);

            if (!empty($this->preRelease)) {
                $version .= '-' . $this->preRelease;
            }

            if (!empty($this->build)) {
                $version .= '+' . $this->build;
            }

            return $version;
        }

        /**
         * Attempt to parse an incomplete version string.
         *
         * Examples: 'v1', 'v1.2', 'v1-beta.4', 'v1.3+007'
         *
         * @param string $version Version string
         *
         * @throws \Bones\SemVer\Exceptions\InvalidVersionException
         *
         * @return self This Version object
         */
        public static function parse(string $version): self
        {
            $semverRegex = '/^v?(?<major>\d+)(?:\.(?<minor>\d+)(?:\.(?<patch>\d+))?)?(?:-(?<pre_release>[0-9A-Za-z-.]+))?(?:\+(?<build>[0-9A-Za-z-.]+)?)?$/';

            if (!preg_match($semverRegex, $version, $matches)) {
                throw new InvalidVersionException('Invalid semantic version string provided');
            }

            $version = sprintf('%s.%s.%s', $matches['major'], $matches['minor'] ?? 0, $matches['patch'] ?? 0);

            if (!empty($matches['pre_release'])) {
                $version .= '-' . $matches['pre_release'];
            }

            if (!empty($matches['build'])) {
                $version .= '+' . $matches['build'];
            }

            return new self($version);
        }

        /**
         * Set (override) the entire version value.
         *
         * @param string $version Version string
         *
         * @throws \Bones\SemVer\Exceptions\InvalidVersionException
         *
         * @return self This Version object
         */
        public function setVersion(string $version): self
        {
            $semverRegex = '/^v?(?<major>\d+)\.(?<minor>\d+)\.(?<patch>\d+)(?:-(?<pre_release>[0-9A-Za-z-.]+))?(?:\+(?<build>[0-9A-Za-z-.]+)?)?$/';

            if (!preg_match($semverRegex, $version, $matches)) {
                throw new InvalidVersionException('Invalid semantic version string provided');
            }

            $this->major = (int)$matches['major'];
            $this->minor = (int)$matches['minor'];
            $this->patch = (int)$matches['patch'];
            $this->preRelease = $matches['pre_release'] ?? null;
            $this->build = $matches['build'] ?? null;

            return $this;
        }

        /**
         * Set the major version to a custom value.
         *
         * @param int $value Positive integer value
         *
         * @return self This Version object
         */
        public function setMajor(int $value): self
        {
            $this->major = $value;
            $this->setMinor(0);

            return $this;
        }

        /**
         * Set the minor version to a custom value.
         *
         * @param int $value Positive integer value
         *
         * @return self This Version object
         */
        public function setMinor(int $value): self
        {
            $this->minor = $value;
            $this->setPatch(0);

            return $this;
        }

        /**
         * Set the patch version to a custom value.
         *
         * @param int $value Positive integer value
         *
         * @return self This Version object
         */
        public function setPatch(int $value): self
        {
            $this->patch = $value;
            $this->setPreRelease(null);
            $this->setBuild(null);

            return $this;
        }

        /**
         * Set the pre-release string to a custom value.
         *
         * @param string|null $value A new pre-release value
         *
         * @return self This Version object
         */
        public function setPreRelease($value): self
        {
            $this->preRelease = $value;

            return $this;
        }

        /**
         * Set the build string to a custom value.
         *
         * @param string|null $value A new build value
         *
         * @return self This Version object
         */
        public function setBuild($value): self
        {
            $this->build = $value;

            return $this;
        }

        /**
         * Get the version string prefixed with a custom string.
         *
         * @param string $prefix String to prepend to the version string
         *                       (default: 'v')
         *
         * @return string Prefixed version string
         */
        public function prefix(string $prefix = 'v'): string
        {
            return $prefix . (string)$this;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Bones
|--------------------------------------------------------------------------
|
|
|
*/

namespace Bones {
    define('WPBONES_MINIMAL_PHP_VERSION', "7.3");

    use Bones\SemVer\Version;

    if (!function_exists('semver')) {
        /**
         * Create a SemVer version object.
         *
         * @throws \Bones\SemVer\Exceptions\InvalidVersionException
         */
        function semver(string $string): Version
        {
            return new Version($string);
        }
    }

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
        const VERSION = '1.0.5';

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
            $this->boot();
        }

        /**
         * Return the current Plugin name and namespace defined in the namespace file.
         *
         * @return array
         */
        public function getPluginNameAndNamespace()
        {
            return explode(",", file_get_contents('namespace'));
        }

        /**
         * Return the current Plugin name defined in the namespace file.
         *
         * @return string
         */
        public function getPluginName()
        {
            [$plugin_name] = $this->getPluginNameAndNamespace();

            return $plugin_name;
        }

        /**
         * Return the current Plugin namespace defined in the namespace file.
         *
         * @return string
         */
        public function getNamespace()
        {
            [$null, $namespace] = $this->getPluginNameAndNamespace();

            return $namespace;
        }

        /**
         * Return the sanitized plugin name.
         *
         * @return string
         */
        public function getSanitizePluginName($str = null)
        {
            if (is_null($str)) {
                $str = $this->getPluginName();
            }

            return $this->sanitize($str);
        }

        /**
         * Return the plugin id used for css, js, less and files.
         * Currently it's the sanitized plugin name.
         *
         * @return string
         */
        public function getPluginId($str = null)
        {
            return $this->getSanitizePluginName($str);
        }

        /**
         * Return the snake case plugin name.
         *
         * @return string
         */
        public function getSnakeCasePluginName($str = null)
        {
            $str = $this->getSanitizePluginName($str);
            return str_replace('-', '_', $str);
        }

        /**
         * Return the plugin slug.
         */
        public function getPluginSlug($str = null)
        {
            $str = $this->getSnakeCasePluginName($str);

            return $str . '_slug';
        }

        /**
         * Return the plugin vars.
         */
        public function getPluginVars($str = null)
        {
            $str = $this->getSnakeCasePluginName($str);

            return $str . '_vars';
        }

        /**
         * Return the defaulr plugin name and namespace.
         */
        protected function getDefaultPlaginNameAndNamespace()
        {
            return ['WP Kirk', 'WPKirk'];
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
            $kernelClass = "{$this->namespace}\\Console\\Kernel";
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
        protected function isCommand($command)
        {
            $arguments = $this->arguments();

            return ($command === ($arguments[0] ?? ""));
        }

        /**
         * Return the arguments after "php bones".
         *
         * @param int $index Optional. Index of argument.
         * If NULL will be returned the whole array.
         *
         * @return mixed|array
         */
        protected function arguments($index = null)
        {
            $argv = $_SERVER['argv'];

            // strip the application name
            array_shift($argv);

            return $index ? ($argv[$index] ?? null) : $argv;
        }

        /**
         * Return the params after "php bones [command]".
         *
         * @param int $index Optional. Index of param.
         * If NULL will be returned the whole array.
         *
         * @return array
         */
        protected function commandParams($index = null)
        {
            $params = $this->arguments();

            // strip the command name
            array_shift($params);

            return $index ? ($params[$index] ?? null) : $params;
        }

        /**
         * Commodity function to check if help has been requested.
         *
         * @param string $str Optional. Command to check.
         *
         * @return bool
         */
        protected function isHelp($str = null)
        {
            $params = $this->commandParams();

            if (!is_null($str)) {
                return (empty($str) || $str === '--help');
            }

            return (!empty($params[0]) && $params[0] === '--help');
        }

        /**
         * This is a special bootstrap in order to avoid the WordPress and kernel environment
         * when we have to rename the plugin and vendor structure.
         *
         */
        public function boot()
        {
            $arguments = $this->arguments();

            // we won't load the WordPress environment for the following commands
            if (empty($arguments) || $this->isCommand('--help')) {
                $this->help();
            }
            // rename
            elseif ($this->isCommand('rename')) {
                $this->rename($this->commandParams());
            }
            // install
            elseif ($this->isCommand('install')) {
                $this->install($this->commandParams());
            }
            // Update
            elseif ($this->isCommand('update')) {
                $this->update();
            }
            // go ahead...
            else {
                $this->loadWordPress();

                $this->loadKernel();

                // handle the rest of the commands except rename
                $this->handle();
            }
        }

        /**
         * Let's roll
         *
         * @return \Bones\BonesCommandLine
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

        /**
         * Display the full help.
         *
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
            $this->info("Current plugin name and namespace:");
            $this->line(" '{$this->getPluginName()}', '{$this->getNamespace()}'\n");
            $this->info("Usage:");
            $this->line(" command [options] [arguments]\n");
            $this->info("Available commands:");
            $this->line(" deploy                  Create a deploy version");
            $this->line(" install                 Install a new WP Bones plugin");
            $this->line(" optimize                Run composer dump-autoload with -o option");
            $this->line(" rename                  Rename the plugin name and the namespace");
            $this->line(" require                 Install a WP Bones package");
            $this->line(" tinker                  Interact with your application");
            $this->line(" update                  Update the Framework");
            $this->line(" version                 Update the Plugin version");
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

        /**
         * Commodity to display an message in the console.
         *
         * @param string $str The message to display.
         */
        protected function line($str)
        {
            echo "\033[38;5;82m" . $str;
            echo "\033[0m\n";
        }

        /**
         * Commodity to display an error message in the console.
         *
         * @param string $str The message to display.
         */
        protected function error($str)
        {
            echo "\033[41m\n";
            echo "\033[41;255m" . $str . "\n";
            echo "\033[0m\n";
        }

        /**
         * Commodity to display a message in the console.
         *
         * @param string $str The message to display.
         */
        protected function info($str)
        {
            echo "\033[38;5;213m" . $str;
            echo "\033[0m\n";
        }

        /**
         * Get input from console
         *
         * @param string $str The question to ask
         * @param string $default The default value
         */
        protected function ask($str, $default = '')
        {
            echo "\n\e[38;5;33m$str" . (empty($default) ? "" : " (default: {$default})") . "\e[0m ";

            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);

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
             *
             * @suppress PHP0405
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
                            $error = preg_match($match, $file, $regexp_result);
                            if (0 !== $error || false !== $error) {
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

        /**
         * Return a kebalized version of the string
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

        /**
         * This is the most important function of WP Bones.
         * Here we will rename all occurrences of the plugin name and namespace.
         * The default plugin name is 'WP Kirk' and the default namespace is 'WPKirk'.
         * Here we will create also the slug used in the plugin.
         *
         * For example, if the plugin name is 'My WP Plugin'
         *
         * My WP Plugin          Name of plugin
         * MyWPPlugin            Namespace, see [PSR-4 autoloading standard](http://www.php-fig.org/psr/psr-4/)
         * my_wp_plugin_slug     Plugin slug
         * my_wp_plugin_vars     Plugin vars used for CPT, taxonomy, etc.
         * my-wp-plugin          Internal id used for css, js and less files
         *
         * As you can see we're going to create all namespace/id from the plugin name.
         *
         * @brief Rename the plugin
         *
         */
        protected function rename($args)
        {
            if ($this->isHelp()) {
                $this->info('Usage:');
                $this->line(' php bones rename [options] <Plugin Name> <Namespace>');
                $this->info('Available options:');
                $this->line(' --reset                 Reset the plugin name and namespace');
                $this->line(' --update                Rename after an update');
                exit;
            }

            $ask_continue = false;

            // use the current plugin name and namespace from the namespace file
            $search_plugin_name = $plugin_name = $this->getPluginName();
            $search_namespace = $namespace = $this->getNamespace();

            // ask plugin name and namespace from console
            if (empty($args[0]) && empty($args[1])) {
                $plugin_name = $this->ask('Plugin name:', $this->getPluginName());
                $namespace = $this->ask('Namespace:', $this->getNamespace());
                $ask_continue = true;
            }

            // Reset the plugin name and namespace to the original values
            if (!empty($args[0]) && $args[0] === '--reset') {
                [$plugin_name, $namespace] = $this->getDefaultPlaginNameAndNamespace();
            }
            // force namespace as WPKirk after a composer update
            elseif (!empty($args[0]) && $args[0] === '--update') {
                [$search_plugin_name, $search_namespace] = $this->getDefaultPlaginNameAndNamespace();
            }
            // new plugin name, the namespace will be create from plugin name
            elseif (!empty($args[0]) && empty($args[1])) {
                [$plugin_name, $null] = $args;
                $namespace = $plugin_name;
                $ask_continue = true;
            }
            // new plugin name and namespace
            elseif (!empty($args[0]) && !empty($args[1])) {
                [$plugin_name, $namespace] = $args;
            }

            // sanitize the namespace
            $namespace = str_replace(" ", "", ucwords($namespace));

            $this->info("\nThe new plugin name and namespace will be '{$plugin_name}', '{$namespace}'");

            if ($ask_continue) {
                $yesno = $this->ask("Continue (y/n)", 'n');

                if (strtolower($yesno) != 'y') {
                    return;
                }
            }

            // start scan everything
            $files = array_filter(array_map(function ($e) {

                // exclude node_modules and bones executable
                if (
                false !== strpos($e, "node_modules") ||
                false !== strpos($e, "vendor/wpbones/wpbones/src/Console/bin/bones")
                ) {
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

                // change namespace
                $content = str_replace($search_namespace, $namespace, $content);

                // change slug
                $content = str_replace($this->getPluginSlug($search_plugin_name), $this->getPluginSlug($plugin_name), $content);

                // change vars
                $content = str_replace($this->getPluginVars($search_plugin_name), $this->getPluginVars($plugin_name), $content);

                // change id
                $content = str_replace($this->getPluginId($search_plugin_name), $this->getPluginId($plugin_name), $content);

                // change plugin name just in index.php and readme.txt
                if ($file === 'index.php' || $file === 'readme.txt') {
                    $content = str_replace($search_plugin_name, $plugin_name, $content);
                }

                file_put_contents($file, $content);
            }

            foreach (glob('localization/*') as $file) {
                $newFile = str_replace($this->getPluginId($search_plugin_name), $this->getPluginId($plugin_name), $file);
                rename($file, $newFile);
            }

            foreach (glob('resources/assets/js/*') as $file) {
                $newFile = str_replace($this->getPluginId($search_plugin_name), $this->getPluginId($plugin_name), $file);
                rename($file, $newFile);
            }

            foreach (glob('resources/assets/less/*') as $file) {
                $newFile = str_replace($this->getPluginId($search_plugin_name), $this->getPluginId($plugin_name), $file);
                rename($file, $newFile);
            }

            // save new plugin name and namespace
            file_put_contents('namespace', "{$plugin_name},{$namespace}");
        }

        /**
         * Execute composer install
         */
        protected function install()
        {
            if ($this->isHelp()) {
                $this->line("Will run the composer install\n");
                $this->info('Usage:');
                $this->line(' php bones install');
                exit;
            }
            $this->line(`composer install`);
        }

        /**
         * Handle the plugin version by SemVer.
         * As you know we have to handle two different plugin version: the first one is the plugin version, the second one is the readme.txt version.
         * This means that we are going to load and check both files.
         * We have to check if the version are the same as well.
         *
         */
        protected function version($argv)
        {
            // get all contents
            $readme_txt_content = file_get_contents('readme.txt');
            $index_php_content = file_get_contents('index.php');

            // parse the readme.txt version
            $lines = explode("\n", $readme_txt_content);
            foreach ($lines as $line) {
                if (preg_match('/^[ \t\/*#@]*Stable\s*(.*)$/i', $line, $matches)) {

                    /**
                     * The version is in the format of: Stable tag: 1.0.0
                     */
                    $stable_tag_version_from_readme_txt = $matches[0];

                    /**
                     * The version is in the format of: 1.0.0
                     */
                    $version_number_from_readme_txt = preg_replace('/[^0-9.]/', '', $matches[1]);

                    $this->line("\nreadme.txt > $version_number_from_readme_txt ($stable_tag_version_from_readme_txt)");
                    break;
                }
            }

            // parse the index.php version
            $lines = explode("\n", $index_php_content);
            foreach ($lines as $line) {

                // get the plugin version for Wordpress comments
                if (preg_match('/^[ \t\/*#@]*Version:\s*(.*)$/i', $line, $matches)) {

                    /**
                     * The version is in the format of: * Version: 1.0.0
                     */
                    $version_string_from_index_php = $matches[0];

                    /**
                     * The version is in the format of: 1.0.0
                     */
                    $version_number_from_index_php = preg_replace('/[^0-9.]/', '', $matches[1]);

                    $this->line("index.php  > {$version_number_from_index_php} ($version_string_from_index_php)");
                    break;
                }
            }

            if ($version_number_from_index_php != $version_number_from_readme_txt) {
                $this->error("\nWARNING:\n\nThe version in readme.txt and index.php are different.");
            }

            if (!isset($argv[0]) || empty($argv[0])) {
                $version = $this->ask('Enter new version of your plugin:');
            } elseif ($this->isHelp()) {
                $this->line("\nUsage:");
                $this->info("  version [plugin version]\n");
                $this->line("Arguments:");
                $this->info("  [plugin version]\tThe version of plugin. Examples: '1', '2.0', 'v1', 'v1.2', 'v1-beta.4', 'v1.3+007'");
                $this->info("  [--major]\tIncrement the major.y.z of plugin.");
                $this->info("  [--minor]\tIncrement the x.minor.z of plugin.");
                $this->info("  [--patch]\tIncrement the x.y.patch of plugin.\n");
                exit(0);
            } elseif (isset($argv[0]) && "--patch" === $argv[0]) {
                $version = semver($version_number_from_index_php)->incrementPatch();
            } elseif (isset($argv[0]) && "--minor" === $argv[0]) {
                $version = semver($version_number_from_index_php)->incrementMinor();
            } elseif (isset($argv[0]) && "--major" === $argv[0]) {
                $version = semver($version_number_from_index_php)->incrementMajor();
            } else {
                $version = trim($argv[0]);
            }

            if ($version === "") {
                $version = semver($version_number_from_index_php)->incrementPatch();
            }

            $version = Version::parse($version);

            $yesno = $this->ask("The new version of your plugin will be {$version}, is it ok? (y/n)", 'n');

            if (strtolower($yesno) != 'y') {
                return;
            }

            if ($version != $version_number_from_index_php || $version != $version_number_from_readme_txt) {

                // We're going to change the "Stable tag: x.y.z" with "Stable tag: $version"
                $new_stable_tag_version_for_readme_txt = str_replace($version_number_from_readme_txt, $version, $stable_tag_version_from_readme_txt);

                // We're going to change the whole "readme.txt" file
                $new_readme_txt_content = str_replace($stable_tag_version_from_readme_txt, $new_stable_tag_version_for_readme_txt, $readme_txt_content);

                file_put_contents('readme.txt', $new_readme_txt_content);

                // We're goinf to change the "* Version: x.y.z" with "* Version: $version"
                $new_version_string_for_index_php = str_replace($version_number_from_index_php, $version, $version_string_from_index_php);

                // We're going to change the whole "index.php" file
                $new_index_php_content = str_replace($version_string_from_index_php, $new_version_string_for_index_php, $index_php_content);

                file_put_contents('index.php', $new_index_php_content);

                return $this->line("\nVersion updated to {$version}");
            }

            $this->line("\nVersion is already {$version}");
        }

        /**
         * Execute composer update
         */
        protected function update()
        {
            if ($this->isHelp()) {
                $this->line("Will run the composer update. Useful if there is a new version of WP Bones\n");
                $this->info('Usage:');
                $this->line(' php bones update');
                exit;
            }
            // update composer module
            $this->line(`composer update`);
        }

        /**
         * Create a deploy version of the plugin
         *
         * @param string $path The path to the deploy version of the plugin
         */
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

                // first of all delete previous path
                $this->info("🕐 Delete folder {$path}");
                $this->deleteDirectory($path);
                $this->info("\033[1A👍");

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

        /**
         * Alias composer dump-autoload
         */
        protected function optimize()
        {
            $this->line(`composer dump-autoload -o`);
        }

        /**
         * Install a new composer package
         *
         * @param string $package The composer package to install
         */
        protected function requirePackage($package)
        {
            if ($this->isHelp($package)) {
                $this->info('Use php bones require <PackageName>');
                exit;
            }

            $this->line(`composer require {$package}`);

            // rename as it is
            $this->rename(['--update']);
        }

        /**
         * Start a Tinker emulation
         */
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

        /**
         * Delete a whole folder
         *
         * @param string $path The path to the folder to delete
         */
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

        /**
         * Copy a whole folder. Used by deploy()
         *
         * @param string $source The source path
         * @param string $dest The target path
         * @param number $permissions The permissions to set
         */
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

        /**
         * Used to skip some files and folders during the deploy
         *
         * @param string $value The file or folder to skip
         */
        protected function skip($value)
        {
            $single = str_replace($this->rootDeploy, '', $value);

            return in_array($single, $this->skipWhenDeploy);
        }

        /**
         * Create a migrate file
         *
         * @param string $table The table name
         */
        protected function createMigrate($tablename)
        {
            if ($this->isHelp($tablename)) {
                $this->info('Use php bones migrate:make <Tablename>');

                return;
            }

            $filename = sprintf(
                '%s_create_%s_table.php',
                date('Y_m_d_His'),
                strtolower($tablename)
            );

            // current plugin name and namespace
            $namespace = $this->getNamespace();

            // get the stub
            $content = file_get_contents("vendor/wpbones/wpbones/src/Console/stubs/migrate.stub");
            $content = str_replace('{Namespace}', $namespace, $content);
            $content = str_replace('{Tablename}', $tablename, $content);

            file_put_contents("database/migrations/{$filename}", $content);

            $this->line(" Created database/migrations/{$filename}");
        }

        /**
         * Create a Custom Post Type controller
         *
         * @param string $className The class name
         */
        protected function createCustomPostType($className)
        {
            if ($this->isHelp($className)) {
                $this->info('Use php bones make:cpt <ClassName>');

                return;
            }

            $filename = sprintf('%s.php', $className);

            // current plugin name and namespace
            [$pluginName, $namespace] = $this->getPluginNameAndNamespace();

            $slug = str_replace("-", "_", $this->sanitize($pluginName));

            $id = $this->ask("Enter a ID:", $slug);
            $name = $this->ask("Enter the name:");
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

        /**
         * Create a Custom Taxonomy controller
         *
         * @param string $className The class name
         */
        protected function createCustomTaxonomyType($className)
        {
            if ($this->isHelp($className)) {
                $this->info('Use php bones make:ctt <ClassName>');

                return;
            }

            $filename = sprintf('%s.php', $className);

            // current plugin name and namespace
            $namespace = $this->getNamespace();

            $slug = $this->getPluginId();

            $id = $this->ask("Enter a ID:", $slug);
            $name = $this->ask("Enter the name:");
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

        /**
         * Create a controller
         *
         * @param string $className The class name
         */
        protected function createController($className)
        {
            if ($this->isHelp($className)) {
                $this->info('Use php bones make:controller <ClassName>');

                return;
            }

            // current plugin name and namespace
            $namespace = $this->getNamespace();

            // get additional path
            $path = $namespacePath = '';
            if (false !== strpos($className, '/')) {
                $parts = explode('/', $className);
                $className = array_pop($parts);
                $path = implode('/', $parts) . '/';
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

        /**
         * Create a Command controller
         *
         * @param string $className The class name
         */
        protected function createCommand($className)
        {
            if ($this->isHelp($className)) {
                $this->info('Use php bones make:console <ClassName>');

                return;
            }

            $filename = sprintf('%s.php', $className);

            // current plugin name and namespace
            [$pluginName, $namespace] = $this->getPluginNameAndNamespace();

            $signature = str_replace("-", "", $this->sanitize($pluginName));
            $command = str_replace("-", "", $this->sanitize($className));

            $signature = $this->ask("Enter a signature:", $signature);
            $command = $this->ask("Enter the command:", $command);

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

        /**
         * Create a Shortcode controller
         *
         * @param string $className The class name
         */
        protected function createShortcode($className)
        {
            if ($this->isHelp($className)) {
                $this->info('Use php bones make:shortcode <ClassName>');

                return;
            }

            $filename = sprintf('%s.php', $className);

            // current plugin name and namespace
            $namespace = $this->getNamespace();

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

        /**
         * Create a Service Provider
         *
         * @param string $className The class name
         */
        protected function createProvider($className)
        {
            if ($this->isHelp($className)) {
                $this->info('Use php bones make:provider <ClassName>');

                return;
            }

            $filename = sprintf('%s.php', $className);

            // current plugin name and namespace
            $namespace = $this->getNamespace();

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

        /**
         * Create a Widget controller
         *
         * @param string $className The class name
         */
        protected function createWidget($className)
        {
            if ($this->isHelp($className)) {
                $this->info('Use php bones make:widget <ClassName>');

                return;
            }

            $filename = sprintf('%s.php', $className);

            // current plugin name and namespace
            [$pluginName, $namespace] = $this->getPluginNameAndNamespace();

            $slug = $this->getPluginId();

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

        /**
         * Create a Ajax controller
         *
         * @param string $className The class name
         */
        protected function createAjax($className)
        {
            if ($this->isHelp($className)) {
                $this->info('Use php bones make:ajax <ClassName>');

                return;
            }

            // current plugin name and namespace
            $namespace = $this->getNamespace();

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

        /**
         * Create a database Model
         *
         * @param string $className The class name
         */
        protected function createModel($className)
        {
            if ($this->isHelp($className)) {
                $this->info('Use php bones make:model <ClassName>');

                return;
            }

            // current plugin name and namespace
            $namespace = $this->getNamespace();

            // get additional path
            $path = $namespacePath = '';
            if (false !== strpos($className, '/')) {
                $parts = explode('/', $className);
                $className = array_pop($parts);
                $path = implode('/', $parts) . '/';
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
         * Run subtask. Handle the php bones commands.
         *
         */
        protected function handle()
        {
            // deploy
            if ($this->isCommand('deploy')) {
                $this->deploy($this->arguments(1));
            }
            // Optimize
            elseif ($this->isCommand('optimize')) {
                $this->optimize();
            }
            // Tinker
            elseif ($this->isCommand('tinker')) {
                $this->tinker();
            }
            // Require
            elseif ($this->isCommand('require')) {
                $this->requirePackage($this->commandParams(0));
            }
            // Version
            elseif ($this->isCommand('version')) {
                $this->version($this->commandParams());
            }
            // migrate:create {table_name}
            elseif ($this->isCommand('migrate:create')) {
                $this->createMigrate($this->commandParams(0));
            }
            // make:controller {controller_name}
            elseif ($this->isCommand('make:controller')) {
                $this->createController($this->commandParams(0));
            }
            // make:console {command_name}
            elseif ($this->isCommand('make:console')) {
                $this->createCommand($this->commandParams(0));
            }
            // make:cpt {className}
            elseif ($this->isCommand('make:cpt')) {
                $this->createCustomPostType($this->commandParams(0));
            }
            // make:shortcode {className}
            elseif ($this->isCommand('make:shortcode')) {
                $this->createShortcode($this->commandParams(0));
            }
            // make:provider {className}
            elseif ($this->isCommand('make:provider')) {
                $this->createProvider($this->commandParams(0));
            }
            // make:ajax {className}
            elseif ($this->isCommand('make:ajax')) {
                $this->createAjax($this->commandParams(0));
            }
            // make:ctt {className}
            elseif ($this->isCommand('make:ctt')) {
                $this->createCustomTaxonomyType($this->commandParams(0));
            }
            // make:widget {className}
            elseif ($this->isCommand('make:widget')) {
                $this->createWidget($this->commandParams(0));
            }
            // make:model {className}
            elseif ($this->isCommand('make:model')) {
                $this->createModel($this->commandParams(0));
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
}
