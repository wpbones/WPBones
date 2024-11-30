#!/usr/bin/env php
<?php
/**
 * This file is part of SemVer.
 *
 * https://github.com/PHLAK/SemVer
 */

namespace Bones\SemVer\Exceptions {

  use Exception;

  class InvalidVersionException extends Exception {}
}

namespace Bones\SemVer\Traits {

  use Bones\SemVer\Version;

  trait Comparable
  {
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

      $preReleases1 = array_pad(
        $v1preReleaseParts,
        count($v2preReleaseParts),
        null
      );
      $preReleases2 = array_pad(
        $v2preReleaseParts,
        count($v1preReleaseParts),
        null
      );

      return $preReleases1 <=> $preReleases2;
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
     * Increment the pre-release version value by one.
     *
     * @return self This Version object
     */
    public function incrementPreRelease(): self
    {
      if (empty($this->preRelease)) {
        $this->incrementPatch();
        $this->setPreRelease('1');

        return $this;
      }

      $identifiers = explode('.', $this->preRelease);

      if (!is_numeric(end($identifiers))) {
        $this->setPreRelease(implode('.', [$this->preRelease, '1']));

        return $this;
      }

      array_push($identifiers, (string)((int)array_pop($identifiers) + 1));

      $this->setPreRelease(implode('.', $identifiers));

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
   * @property int $major      Major release number
   * @property int $minor      Minor release number
   * @property int $patch      Patch release number
   * @property string|null $preRelease Pre-release value
   * @property string|null $build      Build release value
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
     * @throws InvalidVersionException
     */
    public function __construct(string $version = '0.1.0')
    {
      $this->setVersion($version);
    }

    /**
     * Set (override) the entire version value.
     *
     * @param string $version Version string
     *
     * @return self This Version object
     * @throws InvalidVersionException
     *
     */
    public function setVersion(string $version): self
    {
      $semverRegex =
        '/^v?(?<major>\d+)\.(?<minor>\d+)\.(?<patch>\d+)(?:-(?<pre_release>[0-9A-Za-z-.]+))?(?:\+(?<build>[0-9A-Za-z-.]+)?)?$/';

      if (!preg_match($semverRegex, $version, $matches)) {
        throw new InvalidVersionException(
          'Invalid semantic version string provided'
        );
      }

      $this->major = (int)$matches['major'];
      $this->minor = (int)$matches['minor'];
      $this->patch = (int)$matches['patch'];
      $this->preRelease = $matches['pre_release'] ?? null;
      $this->build = $matches['build'] ?? null;

      return $this;
    }

    /**
     * Attempt to parse an incomplete version string.
     *
     * Examples: 'v1', 'v1.2', 'v1-beta.4', 'v1.3+007'
     *
     * @param string $version Version string
     *
     * @return self This Version object
     * @throws InvalidVersionException
     *
     */
    public static function parse(string $version): self
    {
      $semverRegex =
        '/^v?(?<major>\d+)(?:\.(?<minor>\d+)(?:\.(?<patch>\d+))?)?(?:-(?<pre_release>[0-9A-Za-z-.]+))?(?:\+(?<build>[0-9A-Za-z-.]+)?)?$/';

      if (!preg_match($semverRegex, $version, $matches)) {
        throw new InvalidVersionException(
          'Invalid semantic version string provided'
        );
      }

      $version = sprintf(
        '%s.%s.%s',
        $matches['major'],
        $matches['minor'] ?? 0,
        $matches['patch'] ?? 0
      );

      if (!empty($matches['pre_release'])) {
        $version .= '-' . $matches['pre_release'];
      }

      if (!empty($matches['build'])) {
        $version .= '+' . $matches['build'];
      }

      return new self($version);
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

/**
 * Bones
 *
 * @package Bones
 */

namespace Bones {
  /* The minimum PHP version required to run Bones. */
  define('WPBONES_MINIMAL_PHP_VERSION', '7.4');

  /* MARK: The WP Bones command line version. */
  define('WPBONES_COMMAND_LINE_VERSION', '1.8.1');

  use Bones\SemVer\Exceptions\InvalidVersionException;
  use Bones\SemVer\Version;
  use Exception;

  if (!function_exists('semver')) {
    /**
     * Create a SemVer version object.
     *
     * @throws InvalidVersionException
     */
    function semver(string $string): Version
    {
      return new Version($string);
    }
  }

  if (version_compare(PHP_VERSION, WPBONES_MINIMAL_PHP_VERSION) < 0) {
    echo "\n\033[33;5;82mWarning!!\n";
    echo "\n\033[38;5;82m\t" .
      'You must run with PHP version ' .
      WPBONES_MINIMAL_PHP_VERSION .
      ' or greater';
    echo "\033[0m\n\n";
    exit();
  }

  /**
   * @class BonesCommandLine
   */
  class BonesCommandLine
  {
    /**
     * WP Bones version
     */
    const VERSION = WPBONES_COMMAND_LINE_VERSION;

    /**
     * Used for additional kernel command.
     *
     * @var null
     */
    protected $kernel = null;

    /**
     * List of files and folders to skip during the deployment.
     *
     * @var array
     */
    protected array $skipWhenDeploy = [];

    /**
     * Base folder during the deployment.
     *
     * @var string
     */
    protected string $rootDeploy = '';

    /**
     * WP-CLI version
     *
     * @var string|null
     * @since 1.6.0
     */
    protected ?string $wpCliVersion = null;

    public function __construct()
    {
      $this->boot();
    }

    /**
     * This is a special bootstrap in order to avoid the WordPress and kernel environment
     * when we have to rename the plugin and vendor structure.
     */
    public function boot()
    {
      $arguments = $this->arguments();

      // load the WordPress environment and the plugin
      $this->loadWordPress();

      // load the console kernel
      $this->loadKernel();

      // Check WP-CLI
      $output = shell_exec('wp --info 2>&1');
      if (strpos($output, 'WP-CLI version') !== false) {
        preg_match('/WP-CLI version:\s+([\d\.]+)/', $output, $matches);
        $this->wpCliVersion = $matches[1] ?? null;
      }

      // we won't load the WordPress environment for the following commands
      if (empty($arguments) || $this->isCommand('--help')) {
        $this->help();
      } // rename
      elseif ($this->isCommand('rename')) {
        $this->rename($this->commandParams());
      } // install
      elseif ($this->isCommand('install')) {
        $this->install($this->commandParams());
      } // Update
      elseif ($this->isCommand('update')) {
        $this->update();
      } // go ahead...
      else {
        // handle the rest of the commands except rename
        $this->handle();
      }
    }

    /**
     * Return the arguments after "php bones".
     *
     * @param int|null $index Optional. Index of argument.
     *                   If NULL will be returned the whole array.
     *
     * @return mixed|array
     */
    protected function arguments($index = null)
    {
      // Check if 'argv' is set in the server variables
      if (!isset($_SERVER['argv'])) {
        return null;
      }

      $argv = $_SERVER['argv'];

      if (!is_array($argv)) {
        return $argv;
      }

      // Strip the application name
      array_shift($argv);

      // If $index is provided, return the specific argument or null if it doesn't exist
      if (is_int($index)) {
        return $argv[$index] ?? null;
      }

      // Return all arguments if no index is provided
      return $argv;
    }

    /* Load WordPress core and all environment. */
    protected function loadWordPress()
    {
      try {

        /**
         * MARK: Load WordPress
         * We have to load the WordPress environment.
         */
        $currentDir = $_SERVER['PWD'] ?? __DIR__;
        $wpLoadPath = dirname(dirname(dirname($currentDir))) . '/wp-load.php';

        if (!file_exists($wpLoadPath)) {
          echo "\n\033[33;5;82mWarning!!\n";
          echo "\n\033[38;5;82m\t" .
            'You must be inside "wp-content/plugins/" folders';
          echo "\033[0m\n\n";
          exit();
        }

        require $wpLoadPath;
      } catch (Exception $e) {
        echo "\n\033[33;5;82mWarning!!\n";
        echo "\n\033[38;5;82m\t" . 'Can\'t load WordPress';
        echo "\033[0m\n\n";
      }

      try {
        /**
         * --------------------------------------------------------------------------
         * Register The Auto Loader
         * --------------------------------------------------------------------------
         * Composer provides an auto-generated class loader for our app. We just
         * need to use it! Requiring it here means we don't have to load classes
         * manually. Feels great to relax.
         */
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
          require __DIR__ . '/vendor/autoload.php';
        }
      } catch (Exception $e) {
        echo "\n\033[33;5;82mWarning!!\n";
        echo "\n\033[38;5;82m\t" . 'Can\'t load Composer';
      }

      try {
        /**
         * --------------------------------------------------------------------------
         * Load this plugin env
         * --------------------------------------------------------------------------
         */
        if (file_exists(__DIR__ . '/bootstrap/plugin.php')) {
          require_once __DIR__ . '/bootstrap/plugin.php';
        }
      } catch (Exception $e) {
        echo "\n\033[33;5;82mWarning!!\n";
        echo "\n\033[38;5;82m\t" . 'Can\'t load this plugin env';
      }
    }

    /** Check and load for console kernel extensions. */
    protected function loadKernel()
    {
      // current plugin name and namespace
      $namespace = $this->getNamespace();

      $kernelClass = "{$namespace}\\Console\\Kernel";
      $WPBonesKernelClass = "{$namespace}\\WPBones\\Foundation\\Console\\Kernel";

      try {
        if (class_exists($WPBonesKernelClass) && class_exists($kernelClass)) {
          $this->kernel = new $kernelClass();
        }
      } catch (Exception $e) {
        echo "\n\033[33;5;82mWarning!!\n";
        echo "\n\033[38;5;82m\t" . 'Can\'t load console kernel';
      }
    }

    /**
     * Return the current Plugin namespace defined in the namespace file.
     *
     * @return string
     */
    public function getNamespace(): string
    {
      [$null, $namespace] = $this->getPluginNameAndNamespace();

      return $namespace;
    }

    /**
     * Return the current Plugin name and namespace defined in the namespace file.
     *
     * @return array
     */
    public function getPluginNameAndNamespace(): array
    {
      return explode(',', file_get_contents('namespace'));
    }

    /**
     * Return TRUE if the command is in console argument.
     *
     * @param string $command Bones command to check.
     * @return bool
     */
    protected function isCommand(string $command): bool
    {
      $arguments = $this->arguments();

      return $command === ($arguments[0] ?? '');
    }

    /** Display the WP-CLI version in the bones console. */
    protected function wpCliInfoHelp()
    {
      if ($this->wpCliVersion) {
        $this->info("→ WP-CLI version: {$this->wpCliVersion}");
      } else {
        $this->warning("⚠️ WP-CLI not found: we recommend to install it globally - https://wp-cli.org/#installing\n");
      }
    }

    /** Display the full help. */
    protected function help()
    {
      $colorCodes = [
        [51, 45, 39, 33, 27, 21],
        [249, 248, 247, 246, 245, 244],
        [88, 89, 90, 91, 92, 93],
        [76, 82, 46, 40, 34, 28],
        [201, 200, 199, 198, 197, 196],
        [243, 242, 241, 240, 239, 238]
      ];
      $colorCode = $colorCodes[rand(0, count($colorCodes) - 1)];

      echo "\n\033[38;5;{$colorCode[0]}m██╗    ██╗██████╗     ██████╗  ██████╗ ███╗   ██╗███████╗███████╗\033[0m\n";
      echo "\033[38;5;{$colorCode[1]}m██║    ██║██╔══██╗    ██╔══██╗██╔═══██╗████╗  ██║██╔════╝██╔════╝\033[0m\n";
      echo "\033[38;5;{$colorCode[2]}m██║ █╗ ██║██████╔╝    ██████╔╝██║   ██║██╔██╗ ██║█████╗  ███████╗\033[0m\n";
      echo "\033[38;5;{$colorCode[3]}m██║███╗██║██╔═══╝     ██╔══██╗██║   ██║██║╚██╗██║██╔══╝  ╚════██║\033[0m\n";
      echo "\033[38;5;{$colorCode[4]}m╚███╔███╔╝██║         ██████╔╝╚██████╔╝██║ ╚████║███████╗███████║\033[0m\n";
      echo "\033[38;5;{$colorCode[5]}m ╚══╝╚══╝ ╚═╝         ╚═════╝  ╚═════╝ ╚═╝  ╚═══╝╚══════╝╚══════╝\033[0m\n";

      $this->line("                        Bones Version " . self::VERSION . "\n");
      $this->wpCliInfoHelp();
      $this->info('→ Current Plugin Filename, Name, Namespace:', false);
      $this->line(" '{$this->getMainPluginFile()}', '{$this->getPluginName()}', '{$this->getNamespace()}'\n");
      $this->info('Usage:');
      $this->line(" command [options] [arguments]\n");
      $this->info('Available commands:');
      $this->line(' deploy                  Create a deploy version');
      $this->line(' install                 Install a new WP Bones plugin');
      $this->line(' optimize                Run composer dump-autoload with -o option');
      $this->line(' plugin                  Perform plugin operations');
      $this->line(' rename                  Rename the plugin name and the namespace');
      $this->line(' require                 Install a WP Bones package');
      $this->line(' tinker                  Interact with your application');
      $this->line(' update                  Update the Framework');
      $this->line(' version                 Update the Plugin version');
      $this->info('migrate');
      $this->line(' migrate:create          Create a new Migration');
      $this->info('make');
      $this->line(' make:ajax               Create a new Ajax service provider class');
      $this->line(' make:api                Create a new API controller class');
      $this->line(' make:console            Create a new Bones command');
      $this->line(' make:controller         Create a new controller class');
      $this->line(' make:cpt                Create a new Custom Post Type service provider class');
      $this->line(' make:ctt                Create a new Custom Taxonomy Type service provider class');
      $this->line(' make:eloquent-model     Create a new Eloquent database model class');
      $this->line(' make:model              Create a new database model class');
      $this->line(' make:schedule           Create a new schedule (cron) service provider class');
      $this->line(' make:shortcode          Create a new Shortcode service provider class');
      $this->line(' make:provider           Create a new service provider class');
      $this->line(' make:widget             Create a new Widget service provider class');

      if ($this->kernel && $this->kernel->hasCommands()) {
        $this->info('Extensions');
        $this->kernel->displayHelp();
      }

      echo "\n\n";
    }

    /**
     * Commodity to display a message in the console.
     *
     * @param string $str The message to display.
     * @param bool $newLine Optional. Whether to add a new line at the end.
     */
    protected function info(string $str, $newLine = true)
    {
      echo "\033[38;5;213m" . $str;
      echo "\033[0m";
      echo $newLine ? "\n" : '';
    }

    /**
     * Commodity to display a message in the console.
     *
     * @param string $str The message to display.
     * @param bool $newLine Optional. Whether to add a new line at the end.
     */
    protected function line(string $str, $newLine = true)
    {
      echo "\033[38;5;82m" . $str;
      echo "\033[0m";
      echo $newLine ? "\n" : '';
    }

    /* Commodity to display an error message in the console. */
    protected function error(string $str, $newLine = true)
    {
      echo "\033[41m";
      echo $newLine ? "\n" : '';
      echo "\033[41;255m" . $str;
      echo $newLine ? "\n" : '';
      echo "\033[0m";
      echo $newLine ? "\n" : '';
    }

    /* Commodity to display an info message in the console. */
    protected function warning(string $str, $newLine = true)
    {
      echo "\e[31m";
      echo $newLine ? "\n" : '';
      echo  $str;
      echo $newLine ? "\n" : '';
      echo "\e[0m";
      echo $newLine ? "\n" : '';
    }

    /**
     * Return the default Plugin filename as snake case from the plugin name.
     *
     * @param string|null $pluginName
     * @return string
     */
    public function getMainPluginFile($pluginName = ''): string
    {
      if (empty($pluginName)) {
        $pluginName = $this->getPluginName();
      }
      return strtolower(str_replace(' ', '-', $pluginName)) . '.php';
    }

    /**
     * Return the current Plugin name defined in the namespace file.
     *
     * @return string
     */
    public function getPluginName(): string
    {
      [$plugin_name] = $this->getPluginNameAndNamespace();

      return $plugin_name;
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
     * MyWPPlugin            Namespace, see [PSR-4 autoload standard](http://www.php-fig.org/psr/psr-4/)
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
        $this->line(' --update                Rename after an update. For example after install a new package');
        exit();
      }

      $arg_option_plugin_name = $args[0] ?? null;

      switch ($arg_option_plugin_name) {
        case '--reset':
          $this->resetPluginNameAndNamespace();
          break;
        case '--update':
          $this->updatePluginNameAndNamespace();
          break;
        default:
          [$search_plugin_name, $search_namespace, $plugin_name, $namespace, $mainPluginFile] = $this->getAskPluginNameAndNamespace($args);
          $this->info("\nThe new plugin filename, name and namespace will be '{$mainPluginFile}', '{$plugin_name}', '{$namespace}'");
          $yesno = $this->ask('Continue (y/n)', 'n');
          if (strtolower($yesno) != 'y') {
            return;
          }
          $this->setPluginNameAndNamespace($search_plugin_name, $search_namespace, $plugin_name, $namespace);
          $this->optimize();
          break;
      }
    }

    /**
     * Commodity function to check if help has been requested.
     *
     * @param string|null $str Optional. Command to check.
     *
     * @return bool
     */
    protected function isHelp($str = null): bool
    {
      if (!is_null($str)) {
        return empty($str) || $str === '--help';
      }

      $param = $this->commandParams()[0] ?? null;

      return !empty($param) && $param === '--help';
    }

    /**
     * Return the params after "php bones [command]".
     *
     * @param int|null $index Optional. Index of param.
     *                   If NULL will be returned the whole array.
     *
     * @return array|string
     */
    protected function commandParams($index = null)
    {
      $params = $this->arguments();

      // strip the command name
      array_shift($params);

      return !is_null($index) ? $params[$index] ?? null : $params;
    }

    /* Reset the plugin name and namespace to the original values */
    protected function resetPluginNameAndNamespace()
    {
      // use the current plugin name and namespace from the namespace file
      $search_plugin_name = $this->getPluginName();
      $search_namespace = $this->getNamespace();
      [$plugin_name, $namespace] = $this->getDefaultPluginNameAndNamespace();
      $this->setPluginNameAndNamespace($search_plugin_name, $search_namespace, $plugin_name, $namespace);
    }

    /* Return the default plugin name and namespace. */
    protected function getDefaultPluginNameAndNamespace(): array
    {
      return ['WP Kirk', 'WPKirk'];
    }

    /**
     * Update the plugin name and namespace
     *
     * @param string $search_plugin_name The previous plugin name
     * @param string $search_namespace The previous namespace
     * @param string $plugin_name The new plugin name
     * @param string $namespace The new namespace
     */
    protected function setPluginNameAndNamespace(string $search_plugin_name, string $search_namespace, string $plugin_name, string $namespace)
    {
      $mainPluginFile = $this->getMainPluginFile($plugin_name);
      $currentMainPluginFile = $this->getMainPluginFile($search_plugin_name);

      // check if "index.php" exists
      if (file_exists('index.php') && !file_exists($currentMainPluginFile)) {
        $this->info("The file '{$currentMainPluginFile}' doesn't exists. Maybe you are updating from a < 1.5 version.");
        $currentMainPluginFile = 'index.php';
      }

      @rename($currentMainPluginFile, $mainPluginFile);

      // start scan everything
      $files = array_filter(
        array_map(function ($e) {
          // exclude node_modules and bones executable
          if (
            false !== strpos($e, 'node_modules') ||
            false !== strpos($e, 'vendor/wpbones/wpbones/src/Console/bin/bones')
          ) {
            return false;
          }

          return $e;
        }, $this->recursiveScan('*'))
      );

      // merge
      $files = array_merge($files, [
        $mainPluginFile,
        'composer.json',
        'readme.txt',
      ]);

      // change namespace
      $this->line("🚧 Loading and process files...");
      foreach ($files as $file) {

        $content = file_get_contents($file);

        // change namespace
        $content = str_replace($search_namespace, $namespace, $content);

        // change slug
        $content = str_replace(
          $this->getPluginSlug($search_plugin_name),
          $this->getPluginSlug($plugin_name),
          $content
        );

        // change vars
        $content = str_replace(
          $this->getPluginVars($search_plugin_name),
          $this->getPluginVars($plugin_name),
          $content
        );

        // change id
        $content = str_replace(
          $this->getPluginId($search_plugin_name),
          $this->getPluginId($plugin_name),
          $content
        );

        // change plugin name just in main plugin file and readme.txt
        if ($file === $mainPluginFile || $file === 'readme.txt') {
          $content = str_replace($search_plugin_name, $plugin_name, $content);
        }

        file_put_contents($file, $content);
      }
      $this->info("✅ Content renamed completed!");

      $folder = $this->getDomainPath();

      if (!empty($folder) && is_dir($folder)) {
        foreach (glob($folder . '/*') as $file) {
          $newFile = str_replace(
            $this->getPluginId($search_plugin_name),
            $this->getPluginId($plugin_name),
            $file
          );
          rename($file, $newFile);
        }
      }

      foreach (glob('resources/assets/js/*') as $file) {
        $newFile = str_replace(
          $this->getPluginId($search_plugin_name),
          $this->getPluginId($plugin_name),
          $file
        );
        rename($file, $newFile);
      }

      foreach (glob('resources/assets/css/*') as $file) {
        $newFile = str_replace(
          $this->getPluginId($search_plugin_name),
          $this->getPluginId($plugin_name),
          $file
        );
        rename($file, $newFile);
      }

      // Change also the WP Bones plugin class
      $file = 'vendor/wpbones/wpbones/src/Foundation/Plugin.php';
      $content = file_get_contents($file);
      $content = str_replace($currentMainPluginFile, $mainPluginFile, $content);
      file_put_contents($file, $content);

      // save new plugin name and namespace
      file_put_contents('namespace', "{$plugin_name},{$namespace}");

      $this->info("👏 Rename process completed!");
    }

    /**
     * Return an array with all matched files from root folder. This method release the follow filters:
     *
     *     wpdk_rglob_find_dir( true, $file ) - when find a dir
     *     wpdk_rglob_find_file( true, $file ) - when find a a file
     *     wpdk_rglob_matched( $regexp_result, $file, $match ) - after preg_match() done
     *
     * @brief get all matched files
     * @param string $path Folder root
     * @param string $match Optional. Regex to apply on file name. For example use '/^.*\.(php)$/i' to get only php
     *                        file. Default is empty
     *
     * @return array
     * @since 1.0.0.b4
     *
     */
    protected function recursiveScan(string $path, string $match = ''): array
    {
      /**
       * Return an array with all matched files from root folder.
       *
       * @brief    get all matched files
       * @note     Internal recursive use only
       *
       * @param string $path Folder root
       * @param string $match Optional. Regex to apply on file name. For example use '/^.*\.(php)$/i' to get only php file
       * @param array  &$result Optional. Result array. Empty form first call
       *
       * @return array
       *
       * @suppress PHP0405
       */
      function _rglob(string $path, string $match = '', array &$result = []): array
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

    /**
     * Return the plugin slug.
     *
     * @param string|null $str
     */
    public function getPluginSlug($str = null): string
    {
      $str = $this->snakeCasePluginName($str);

      return $str . '_slug';
    }

    /**
     * Return the snake case plugin name.
     *
     * @param string|null $str
     * @return string
     */
    public function snakeCasePluginName($str = null): string
    {
      $str = $this->sanitizePluginName($str);

      return str_replace('-', '_', $str);
    }

    /**
     * Return the sanitized plugin name.
     *
     * @param string|null $str
     * @return string
     */
    public function sanitizePluginName($str = null): string
    {
      if (is_null($str)) {
        $str = $this->getPluginName();
      }

      return $this->sanitize($str);
    }

    /**
     * Return a kebalized version of the string
     *
     * @param string $title
     */
    protected function sanitize(string $title): string
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

      return trim($title, '-');
    }

    /**
     * Return the plugin vars.
     *
     * @param string|null $str
     * @return string
     */
    public function getPluginVars($str = null): string
    {
      $str = $this->snakeCasePluginName($str);

      return $str . '_vars';
    }

    /**
     * Return the plugin id used for css, js, less and files.
     * Currently, it's the sanitized plugin name.
     *
     * @param string|null $str
     *
     * @return string
     */
    public function getPluginId($str = null): string
    {
      return $this->sanitizePluginName($str);
    }

    /* Update the plugin name and namespace after a install new package */
    protected function updatePluginNameAndNamespace()
    {
      // use the current plugin name and namespace from the namespace file
      $plugin_name = $this->getPluginName();
      $namespace = $this->getNamespace();
      [$search_plugin_name, $search_namespace] = $this->getDefaultPluginNameAndNamespace();
      $this->setPluginNameAndNamespace($search_plugin_name, $search_namespace, $plugin_name, $namespace);
    }

    /**
     * Get the plugin name and namespace from args or ask them from the console
     *
     * @param array $args The arguments from the console. The first argument is the plugin name and the second is the namespace
     *
     */
    protected function getAskPluginNameAndNamespace(array $args): array
    {
      // Get the current plugin name and namespace
      $search_plugin_name = $this->getPluginName();
      $search_namespace = $this->getNamespace();

      if ($search_plugin_name === 'WP Kirk' && $search_namespace === 'WPKirk') {
        $this->line("ℹ️  You are renaming your plugin for the first time.\n");
      }

      $plugin_name = $args[0] ?? '';
      $namespace = $args[1] ?? '';

      // Sanitize the namespace by removing spaces and any backslashes
      $namespace = $this->sanitizeToCamelCase($namespace);

      // You may set just the plugin name and the namespace will be created from plugin name
      if (!empty($plugin_name) && empty($namespace)) {
        $namespace = $this->sanitizeToCamelCase($plugin_name);
        $mainPluginFile = $this->getMainPluginFile($plugin_name);
        return [$search_plugin_name, $search_namespace, $plugin_name, $namespace, $mainPluginFile];
      }

      if (!empty($plugin_name) && !empty($namespace)) {
        $mainPluginFile = $this->getMainPluginFile($plugin_name);
        return [$search_plugin_name, $search_namespace, $plugin_name, $namespace, $mainPluginFile];
      }

      $this->info('🚦 ---------------------------------------------------------------------------------');
      $this->info("🚦 Remember the new plugin name and namespace must not contain \"WP Kirk\" or \"WPKirk\"");
      $this->info("🚦 ---------------------------------------------------------------------------------\n");

      $plugin_name = '';
      $namespace = '';

      while (empty($plugin_name)) {
        $plugin_name = $this->ask('Plugin name:', $plugin_name);
        $namespace = $this->ask('Namespace:', $namespace);

        // both plugin name and namespace don't have to contains 'WP Kirk' or 'WPKirk'
        if (strpos($plugin_name, 'WP Kirk') !== false || strpos($plugin_name, 'WPKirk') !== false) {
          $this->error('🛑 Plugin name cannot contain "WP Kirk" or "WPKirk"');
          $plugin_name = '';
          continue;
        }

        if (strpos($namespace, 'WP Kirk') !== false || strpos($namespace, 'WPKirk') !== false) {
          $this->error('🛑 Namespace cannot contain "WP Kirk" or "WPKirk"');
          $plugin_name = '';
          $namespace = '';
          continue;
        }
      }

      // You may set just the plugin name and the namespace will be created from plugin name
      if (empty($namespace)) {
        $namespace = $this->sanitizeToCamelCase($plugin_name);
      }

      $mainPluginFile = $this->getMainPluginFile($plugin_name);

      return [$search_plugin_name, $search_namespace, $plugin_name, $namespace, $mainPluginFile];
    }

    /**
     * Extracts the first comment block from a PHP file.
     *
     * @return string The first comment block found, or an empty string if none found.
     */
    public function extractHeader(): string
    {
      $filePath = $this->getMainPluginFile();

      // Ensure the file exists
      if (!file_exists($filePath)) {
        return ''; // File doesn't exist
      }

      // Read the first 8KB of the file
      $content = file_get_contents($filePath, false, null, 0, 8192);

      if ($content === false) {
        return ''; // File couldn't be read
      }

      // Use a regular expression to find the first comment block
      if (preg_match('/\/\*\*.*?\*\//s', $content, $matches)) {
        return $matches[0];
      }

      return ''; // No comment block found
    }

    /**
     * Extracts specified fields from a WordPress plugin file header.
     *
     * @param string|array ...$fields Field(s) to extract. Can be a string, an array, or multiple string arguments.
     * @return array An associative array of extracted fields and their values.
     */
    public function extractPluginHeaderInfo(...$fields): array
    {
      $filePath = $this->getMainPluginFile();

      // Flatten and unique the fields array
      $fields = array_unique(array_merge(...array_map(function ($field) {
        return is_array($field) ? $field : [$field];
      }, $fields)));

      // Read the first 8KB of the file
      $content = file_get_contents($filePath, false, null, 0, 8192);

      if ($content === false) {
        return []; // File couldn't be read
      }

      $result = [];

      foreach ($fields as $field) {
        // Escape the field name for use in regex
        $escapedField = preg_quote($field, '/');

        // Use a regular expression to find the field
        if (preg_match("/^[ \t\/*#@]*{$escapedField}:[ \t]*(.+)$/m", $content, $matches)) {
          $result[$field] = trim($matches[1]);
        }
      }

      return $result;
    }

    /**
     * Return the CamelCase plugin name.
     * Used for Namespace
     *
     * @param string $input
     * @return string
     */
    public function sanitizeToCamelCase(string $input): string
    {
      // Remove special characters except letters and numbers
      $sanitized = preg_replace('/[^a-zA-Z0-9\s]/', '', $input);

      // Split the string into words (using spaces as delimiter)
      $words = explode(' ', $sanitized);

      // Convert the first letter of each word to uppercase
      $camelCasedWords = array_map('ucfirst', $words);

      // Join the words without spaces
      $camelCasedString = implode('', $camelCasedWords);

      // Return the CamelCase string
      return $camelCasedString;
    }

    /**
     * --------------------------------------------------------------------------
     * Internal useful function
     * --------------------------------------------------------------------------
     * Contains all internal methods.
     */


    /**
     * Get input from console
     *
     * @param string $str The question to ask
     * @param string|null $default The default value
     */
    protected function ask(string $str, ?string $default = ''): string
    {
      $str = "\n\e[38;5;33m$str" .
        (empty($default) ? '' : " (default: {$default})") .
        "\e[0m ";

      // Use readline to get the user input
      $line = readline($str);

      // Trim the input to remove extra spaces or newlines
      $line = trim($line);

      return $line ?: $default;
    }



    /* Alias composer dump-autoload */
    protected function optimize()
    {
      $this->line(`composer dump-autoload -o`);
    }

    /* Execute composer install */
    protected function install()
    {
      if ($this->isHelp()) {
        $this->line("Will run the composer install\n");
        $this->info('Usage:');
        $this->line(' php bones install');
        exit();
      }
      $this->line(`composer install`);
    }

    /* Execute composer update */
    protected function update()
    {
      if ($this->isHelp()) {
        $this->line(
          "Will run the composer update. Useful if there is a new version of WP Bones\n"
        );
        $this->info('Usage:');
        $this->line(' php bones update');
        exit();
      }
      // delete the current vendor/wpbones/wpbones folder
      $this->deleteDirectory('vendor/wpbones/wpbones');

      // update composer module
      $this->line(`composer update`);
    }

    /**
     * Delete a whole folder
     *
     * @param string $path The path to the folder to delete
     */
    public function deleteDirectory(string $path)
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
     * --------------------------------------------------------------------------
     * Public task
     * --------------------------------------------------------------------------
     * Contains tasks a user can run from the console.
     */

    /* Run subtask. Handle the php bones commands */
    protected function handle()
    {
      // deploy
      if ($this->isCommand('deploy')) {
        $this->deploy($this->commandParams());
      } // Optimize
      elseif ($this->isCommand('optimize')) {
        $this->optimize();
      } // Tinker
      elseif ($this->isCommand('tinker')) {
        $this->tinker();
      } // Require
      elseif ($this->isCommand('require')) {
        $this->requirePackage($this->commandParams(0));
      } // Version
      elseif ($this->isCommand('version')) {
        $this->version($this->commandParams());
      } // migrate:create {table_name}
      elseif ($this->isCommand('plugin')) {
        $this->plugin($this->commandParams());
      } // migrate:create {table_name}
      elseif ($this->isCommand('migrate:create')) {
        $this->createMigrate($this->commandParams(0));
      } // make:controller {controller_name}
      elseif ($this->isCommand('make:controller')) {
        $this->createController($this->commandParams(0));
      } // make:console {command_name}
      elseif ($this->isCommand('make:console')) {
        $this->createCommand($this->commandParams(0));
      } // make:cpt {className}
      elseif ($this->isCommand('make:cpt')) {
        $this->createCustomPostType($this->commandParams(0));
      } // make:shortcode {className}
      elseif ($this->isCommand('make:shortcode')) {
        $this->createShortcode($this->commandParams(0));
      } // make:schedule {className}
      elseif ($this->isCommand('make:schedule')) {
        $this->createSchedule($this->commandParams(0));
      } // make:provider {className}
      elseif ($this->isCommand('make:provider')) {
        $this->createProvider($this->commandParams(0));
      } // make:ajax {className}
      elseif ($this->isCommand('make:ajax')) {
        $this->createAjax($this->commandParams(0));
      } // make:ctt {className}
      elseif ($this->isCommand('make:ctt')) {
        $this->createCustomTaxonomyType($this->commandParams(0));
      } // make:widget {className}
      elseif ($this->isCommand('make:widget')) {
        $this->createWidget($this->commandParams(0));
      } // make:model {className}
      elseif ($this->isCommand('make:model')) {
        $this->createModel($this->commandParams(0));
      } // make:eloquent-model {className}
      elseif ($this->isCommand('make:eloquent-model')) {
        $this->createEloquentModel($this->commandParams(0));
      } // make:api {className}
      elseif ($this->isCommand('make:api')) {
        $this->createAPIController($this->commandParams(0));
      } // else...
      else {
        $extended = false;

        if ($this->kernel) {
          $extended = $this->kernel->handle($this->arguments());
        }

        if (!$extended) {
          $this->info("\nUnknown command! Use --help for commands list\n");
        }
      }
    }

    /**
     * Create a deployment version of the plugin
     *
     * @param $argv
     */
    protected function deploy($argv)
    {
      $path = $argv[0] !== '--wp' ? $argv[0] : '';
      $index = $argv[0] !== '--wp' ? 1 : 0;
      $is_wp_org = array_key_exists($index, $argv) && $argv[$index] === '--wp';

      $path = rtrim($path, '/');

      if (empty($path)) {
        $path = $this->ask('Enter the complete path of deploy:');
      } elseif ('--help' === $path) {
        $this->line("\nUsage:");
        $this->info("  deploy <path>\n");
        $this->line('Arguments:');
        $this->info("  path\t\tThe complete path of deploy.");
        $this->info("  [--wp]\tYou are going to release this plugin in the WordPress.org public repository.");
        exit(0);
      }

      $this->info("\nStarting deploy ¯\_(ツ)_/¯\n");

      if (!empty($path)) {

        $dontSkipWhenDeploy = [
          '/gulpfile.js',
          '/package.json',
          '/package-lock.json',
          '/yarn.lock',
          '/tsconfig.json',
          '/pnpm-lock.yaml',
          '/resources/assets',
          '/composer.json',
          '/composer.lock',
        ];

        if ($is_wp_org) {
          $this->info("\n🚦 You are going to release this plugin in the WordPress.org public repository");
          $this->line("\n→ In this case some files won't be skipped during the deployment.");
          $this->line("→ The files that won't be skipped are:\n\n" . implode("\n", $dontSkipWhenDeploy) . "\n\n");
        }

        // alternative method to customize the deployment
        @include 'deploy.php';

        do_action('wpbones_console_deploy_start', $this, $path);

        // first delete previous path
        $this->info("🕐 Delete folder {$path}");
        $this->deleteDirectory($path);
        $this->info("\033[1A👍");

        do_action('wpbones_console_deploy_before_build_assets', $this, $path);

        $packageManager = $this->getAvailablePackageManager();

        if ($packageManager) {

          $this->info("✅ Found '$packageManager' as package manager");

          $answer = $this->ask("Do you want to run '$packageManager run build' to build assets? (y/n)", 'y');

          if (strtolower($answer) === 'y') {
            $this->info("📦 Build for production by using '{$packageManager} run build'");
            shell_exec("{$packageManager} run build");
            $this->info("✅ Built assets successfully");
            do_action('wpbones_console_deploy_after_build_assets', $this, $path);
          } else {
            $answer = $this->ask("Enter the package manager to build assets (press RETURN to skip the build)", '');
            if (empty($answer)) {
              $this->info('⏭︎ Skip build assets');
            } else {
              $this->info("📦 Build for production by using '{$answer} run build'");
              shell_exec("{$answer} run build");
              $this->info("✅ Built assets successfully");
              do_action('wpbones_console_deploy_after_build_assets', $this, $path);
            }
          }
        } else {
          $this->info("🛑 No package manager found. The build assets will be skipped");
        }

        // files and folders to skip
        $this->skipWhenDeploy = [
          '/.git',
          '/.cache',
          '/assets',
          '/.gitignore',
          '/.gitkeep',
          '/.DS_Store',
          '/.babelrc',
          '/node_modules',
          '/bones',
          '/vendor/wpbones/wpbones/src/Console/stubs',
          '/vendor/wpbones/wpbones/src/Console/bin',
          '/vendor/bin/bladeonecli',
          '/vendor/eftec/bladeone/lib/bladeonecli',
          '/deploy.php',
          '/namespace',
          '/README.md',
          '/webpack.mix.js',
          '/webpack.config.js',
          '/phpcs.xml.dist',
          '/mix-manifest.json',
          '/release.sh',
        ];

        // The new strict rules require that the composer must also be released to publish the plugin in the WordPress.org repository.
        if (!$is_wp_org) {
          $this->skipWhenDeploy = array_merge($this->skipWhenDeploy, $dontSkipWhenDeploy);
        }

        /**
         * Filter the list of files and folders to skip during the deployment.
         *
         * @param array $array The files and folders are relative to the root of plugin.
         */
        $this->skipWhenDeploy = apply_filters(
          'wpbones_console_deploy_skip_folders',
          $this->skipWhenDeploy
        );

        $this->rootDeploy = __DIR__;

        $this->info("🚧 Copying to {$path}");
        $this->xcopy(__DIR__, $path);
        $this->info("✅ Copy completed");

        /**
         * Fires when the console deploy is completed.
         *
         * @param mixed $bones Bones command instance.
         * @param string $path The deployed path.
         */
        do_action('wpbones_console_deploy_completed', $this, $path);

        $this->info("\n\e[5m👏 Deploy completed!\e[0m");
        $this->info("\n🚀 You can now deploy the plugin from the path: {$path}\n");
      }
    }

    /**
     * Returns the available package manager
     *
     * @return string|null The name of the available package manager (yarn, npm, pnpm, bun) or null if none is available.
     */
    protected function getAvailablePackageManager(): ?string
    {
      $packageManagers = ['yarn', 'npm', 'pnpm', 'bun'];

      foreach ($packageManagers as $manager) {
        if ($this->isCommandAvailable($manager)) {
          return $manager;
        }
      }

      return null;
    }

    /**
     * Return true if the command is available in the system.
     *
     * @param string $command
     * @return bool
     */
    protected function isCommandAvailable(string $command): bool
    {
      $whereCommand = (PHP_OS_FAMILY === 'Windows') ? 'where' : 'command -v';
      $output = shell_exec("$whereCommand $command");
      return !empty($output);
    }

    /**
     * Copy a whole folder. Used by deploy()
     *
     * @param string $source The source path
     * @param string $dest The target path
     * @param int|null $permissions The permissions to set
     *
     * @return bool
     */
    protected function xcopy(string $source, string $dest, ?int $permissions = 0755): bool
    {
      // Check for symlinks
      if (is_link($source)) {
        return symlink(readlink($source), $dest);
      }

      // Simple copy for a file
      if (is_file($source)) {
        // if the file starts with "." or is in the skip list
        // we don't copy it

        if (strpos(basename($source), '.') === 0 || $this->skip($source)) {
          return false;
        }
        return copy($source, $dest);
      }

      // Make destination directory
      if (!is_dir($dest)) {
        mkdir($dest, $permissions);
      }

      // Loop through the folder
      $dir = dir($source);

      while (false !== ($entry = $dir->read())) {
        // files and folder to skip
        if (
          $entry === '.' ||
          $entry === '..' ||
          strpos($entry, '.') === 0 ||
          $this->skip("{$source}/{$entry}")
        ) {
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
     * Used to skip some files and folders during the deployment
     *
     * @param string $value The file or folder to skip
     *
     * @return bool
     */
    protected function skip(string $value): bool
    {
      $single = str_replace($this->rootDeploy, '', $value);

      return in_array($single, $this->skipWhenDeploy);
    }

    /* Start a Tinker emulation */
    protected function tinker()
    {
      $eval = $this->ask('>>>');

      try {
        if ($eval == 'exit') {
          exit();
        }

        if (substr($eval, -1) != ';') {
          $eval .= ';';
        }

        $this->line(eval($eval));
      } catch (Exception $e) {
        $this->info(eval($e->getMessage()));
      } finally {
        $this->tinker();
      }
    }

    /**
     * Install a new composer package
     *
     * @param string $package The composer package to install
     */
    protected function requirePackage(string $package)
    {
      if ($this->isHelp($package)) {
        $this->info('Use php bones require <PackageName>');
        exit();
      }

      $this->line(`composer require {$package}`);

      // rename as it is
      $this->rename(['--update']);
    }

    /**
     * Handle the plugin version by SemVer.
     * As you know we have to handle two different plugin version: the first one is the plugin version, the second one
     * is the readme.txt version. This means that we are going to load and check both files. We have to check if the
     * version are the same as well.
     *
     * @throws InvalidVersionException
     */
    protected function version($argv)
    {
      $version_number_from_index_php = '';
      $version_number_from_readme_txt = '';
      $stable_tag_version_from_readme_txt = '';
      $version_string_from_index_php = '';

      $mainPluginFilename = $this->getMainPluginFile();

      // get all contents
      $readme_txt_content = file_get_contents('readme.txt');
      $index_php_content = file_get_contents($mainPluginFilename);

      // parse the readme.txt version
      $lines = explode("\n", $readme_txt_content);
      foreach ($lines as $line) {
        if (preg_match('/^[ \t\/*#@]*Stable tag:\s*(.*)$/i', $line, $matches)) {
          /**
           * The version is in the format of: Stable tag: 1.0.0
           */
          $stable_tag_version_from_readme_txt = $matches[0];

          /**
           * The version is in the format of: 1.0.0 or 1.0.0-beta.1 or 1.0.0-alpha.1 or 1.0.0-rc.1
           */
          $version_number_from_readme_txt = $matches[1];

          $this->line(
            "\nreadme.txt > $version_number_from_readme_txt ($stable_tag_version_from_readme_txt)"
          );
          break;
        }
      }

      // parse the $mainPluginFilename version
      $lines = explode("\n", $index_php_content);
      foreach ($lines as $line) {
        // get the plugin version for WordPress comments
        if (preg_match('/^[ \t\/*#@]*Version:\s*(.*)$/i', $line, $matches)) {
          /**
           * The version is in the format of: * Version: 1.0.0
           */
          $version_string_from_index_php = $matches[0];

          /**
           * The version is in the format of: 1.0.0
           */
          $version_number_from_index_php = $matches[1];

          $this->line(
            "$mainPluginFilename  > {$version_number_from_index_php} ($version_string_from_index_php)"
          );
          break;
        }
      }

      if ($version_number_from_index_php != $version_number_from_readme_txt) {
        $this->error(
          "\nWARNING:\n\nThe version in readme.txt and $mainPluginFilename are different."
        );
      }

      if (!isset($argv[0]) || empty($argv[0])) {
        $version = $this->ask('Enter new version of your plugin:');
      } elseif ($this->isHelp()) {
        $this->line("\nUsage:");
        $this->info("  version [plugin version]\n");
        $this->line('Arguments:');
        $this->info(
          "  [plugin version]\t\tThe version of plugin. Examples: '2.0',  'v1.2',  '1.2.0-rc.40', 'v1-beta.4'"
        );
        $this->info("  [--major]\t\t\tIncrement the <major>.y.z of plugin.");
        $this->info("  [--minor]\t\t\tIncrement the x.<minor>.z of plugin.");
        $this->info("  [--patch]\t\t\tIncrement the x.y.<patch> of plugin.");
        $this->info(
          "  [--pre-patch] <prefix>\tIncrement the x.y.<patch>-<prefix>.<i> of plugin."
        );
        $this->info(
          "  [--pre-minor] <prefix>\tIncrement the x.<minor>.z-<prefix>.<i> of plugin."
        );
        $this->info(
          "  [--pre-major] <prefix>\tIncrement the <major>.y.z-<prefix>.<i> of plugin.\n"
        );
        exit(0);
      } elseif (isset($argv[0]) && '--patch' === $argv[0]) {
        $version = semver($version_number_from_index_php)->incrementPatch();
      } elseif (isset($argv[0]) && '--minor' === $argv[0]) {
        $version = semver($version_number_from_index_php)->incrementMinor();
      } elseif (isset($argv[0]) && '--major' === $argv[0]) {
        $version = semver($version_number_from_index_php)->incrementMajor();
      } elseif (
        isset($argv[0]) &&
        in_array($argv[0], ['--pre-patch', '--pre-major', '--pre-minor'])
      ) {
        $prefix = $argv[1] ?? 'rc';

        $methods = [
          '--pre-patch' => 'incrementPatch',
          '--pre-major' => 'incrementMajor',
          '--pre-minor' => 'incrementMinor',
        ];
        // if $version_number_from_index_php is not a pre-release version
        if (strpos($version_number_from_index_php, $prefix) === false) {
          $prerelease = semver($version_number_from_index_php)->{$methods[$argv[0]]}();
          $version = semver($prerelease)
            ->setPreRelease($prefix)
            ->incrementPreRelease();
        } else {
          $version = semver(
            $version_number_from_index_php
          )->incrementPreRelease();
        }
      } else {
        $version = trim($argv[0]);
      }

      if ($version === '') {
        $version = semver($version_number_from_index_php)->incrementPatch();
      }

      try {
        $version = Version::parse($version);
      } catch (InvalidVersionException $e) {
        $this->error("\nERROR:\n\nThe version is not valid.\n");
        exit(1);
      }

      $yesno = $this->ask(
        "The new version of your plugin will be {$version}, is it ok? (y/n)",
        'n'
      );

      if (strtolower($yesno) != 'y') {
        return;
      }

      if (
        $version != $version_number_from_index_php ||
        $version != $version_number_from_readme_txt
      ) {
        // We're going to change the "Stable tag: x.y.z" with "Stable tag: $version"
        $new_stable_tag_version_for_readme_txt = str_replace(
          $version_number_from_readme_txt,
          $version,
          $stable_tag_version_from_readme_txt
        );

        // We're going to change the whole "readme.txt" file
        $new_readme_txt_content = str_replace(
          $stable_tag_version_from_readme_txt,
          $new_stable_tag_version_for_readme_txt,
          $readme_txt_content
        );

        file_put_contents('readme.txt', $new_readme_txt_content);

        // We're going to change the "* Version: x.y.z" with "* Version: $version"
        $new_version_string_for_index_php = str_replace(
          $version_number_from_index_php,
          $version,
          $version_string_from_index_php
        );

        // We're going to change the whole main plugin file
        $new_index_php_content = str_replace(
          $version_string_from_index_php,
          $new_version_string_for_index_php,
          $index_php_content
        );

        file_put_contents($mainPluginFilename, $new_index_php_content);

        $this->line("\nVersion updated to {$version}");

        return;
      }

      $this->line("\nVersion is already {$version}");
    }

    /**
     * Plugin actions
     *
     * @since 1.6.1
     */
    protected function plugin($args)
    {
      if ($this->isHelp()) {
        $this->info('Usage:');
        $this->line(' php bones plugin [options]');
        $this->info('Available options:');
        $this->line(' --check-header              Check the plugin header');
        exit();
      }

      if (isset($args[0]) && '--check-header' === $args[0]) {
        return $this->checkPluginHeader();
      }

      echo $this->extractHeader() . "\n";
    }

    /**
     * Check the plugin header
     *
     * @since 1.6.1
     */
    protected function checkPluginHeader()
    {
      $headerKeys = [
        'Plugin Name',
        'Version',
        'Author',
        'Author URI',
        'Description',
        'Text Domain',
        'Domain Path',
        'Network',
        'Requires at least',
        'Requires PHP',
        'License',
        'License URI',
        'GitHub Plugin URI',
        'GitHub Branch',
      ];
      $header = $this->extractPluginHeaderInfo($headerKeys);

      foreach ($headerKeys as $key) {
        if (isset($header[$key])) {
          echo '✅ ' . $key . ': ';
          $this->info($header[$key]);
        } else {
          $this->warning('👀 ' . $key . ': ', false);
          $this->error(" Not found \n", false);
        }
      }
    }

    /**
     * Help method to get the Domain Path from the plugin header
     *
     * @return string|null
     */
    protected function getDomainPath()
    {
      $header = $this->extractPluginHeaderInfo('Domain Path');
      if (isset($header['Domain Path'])) {
        return $header['Domain Path'];
      }
      return null;
    }

    /**
     * Create a migrate file
     *
     * @param string $tablename
     */
    protected function createMigrate(string $tablename)
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

      // stubbing
      $content = $this->prepareStub('migrate', [
        '{Namespace}' => $namespace,
        '{Tablename}' => $tablename,
      ]);

      file_put_contents("database/migrations/{$filename}", $content);

      $this->line(" Created database/migrations/{$filename}");
    }

    /**
     * Return the content of a stub file with all replacements.
     *
     * @param string $filename The stub file name without extension
     * @param array $replacements
     * @return string
     */
    public function prepareStub(string $filename, array $replacements): string
    {
      $stub = $this->getStubContent($filename);

      return str_replace(
        array_keys($replacements),
        array_values($replacements),
        $stub
      );
    }

    /**
     * Return the content of a stub file.
     *
     * @param string $filename
     * @return string
     */
    public function getStubContent(string $filename): string
    {
      return file_get_contents(
        "vendor/wpbones/wpbones/src/Console/stubs/{$filename}.stub"
      );
    }

    /**
     * Create a controller
     *
     * @param string|null $className The class name
     */
    protected function createController(?string $className = '')
    {
      if ($this->isHelp($className)) {
        $this->info('Use php bones make:controller <ClassName>');
        $this->line('Or');
        $this->info('Use php bones make:controller <Folder>/<ClassName>');

        return;
      }

      // ask className if empty
      $className = $this->askClassNameIfEmpty($className);

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

      // stubbing
      $content = $this->prepareStub('controller', [
        '{Namespace}' => $namespace,
        '{ClassName}' => $className,
      ]);

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
     * Commodity function to check if ClassName has been requested.
     *
     * @param string|null $className Optional. Command to check.
     *
     * @return string
     */
    protected function askClassNameIfEmpty(?string $className = ''): string
    {
      if (empty($className)) {
        $className = $this->ask('ClassName:');
        if (empty($className)) {
          $this->error('ClassName is required');
          exit(0);
        }
      }

      return $className;
    }

    /**
     * Create a Command controller
     *
     * @param string|null $className The class name
     */
    protected function createCommand(?string $className = '')
    {
      if ($this->isHelp($className)) {
        $this->info('Use php bones make:console <ClassName>');

        return;
      }

      // ask className if empty
      $className = $this->askClassNameIfEmpty($className);

      $filename = sprintf('%s.php', $className);

      // current plugin name and namespace
      [$pluginName, $namespace] = $this->getPluginNameAndNamespace();

      $signature = str_replace('-', '', $this->sanitize($pluginName));
      $command = str_replace('-', '', $this->sanitize($className));

      $signature = $this->ask('Enter a signature:', $signature);
      $command = $this->ask('Enter the command:', $command);

      // stubbing
      $content = $this->prepareStub('command', [
        '{Namespace}' => $namespace,
        '{ClassName}' => $className,
        '{Signature}' => $signature,
        '{CommandName}' => $command,
      ]);

      if (!is_dir('plugin/Console/Commands')) {
        mkdir('plugin/Console/Commands', 0777, true);
      }

      file_put_contents("plugin/Console/Commands/{$filename}", $content);

      $this->line(" Created plugin/Console/Commands/{$filename}");

      // check if plugin/Console/Kernel.php already exists
      if (file_exists('plugin/Console/Kernel.php')) {
        $this->info(
          "Remember to add {$className} in the plugin/Console/Commands/Kernel.php property array \$commands"
        );
      } else {
        // stubbing
        $content = $this->prepareStub('kernel', [
          '{Namespace}' => $namespace,
          '{ClassName}' => $className,
        ]);

        file_put_contents('plugin/Console/Kernel.php', $content);

        $this->line(' Created plugin/Console/Kernel.php');
      }
    }

    /**
     * Create a Custom Post Type controller
     *
     * @param string|null $className The class name
     */
    protected function createCustomPostType(?string $className = '')
    {
      if ($this->isHelp($className)) {
        $this->info('Use php bones make:cpt <ClassName>');

        return;
      }

      // ask className if empty
      $className = $this->askClassNameIfEmpty($className);

      $filename = sprintf('%s.php', $className);

      // current plugin name and namespace
      [$pluginName, $namespace] = $this->getPluginNameAndNamespace();

      $slug = str_replace('-', '_', $this->sanitize($pluginName));

      $id = $this->ask('Enter a ID:', $slug);
      $name = $this->ask('Enter the name:');
      $plural = $this->ask('Enter the plural name:');

      if (empty($id)) {
        $id = $slug;
      }

      // stubbing
      $content = $this->prepareStub('cpt', [
        '{Namespace}' => $namespace,
        '{ClassName}' => $className,
        '{ID}' => $id,
        '{Name}' => $name,
        '{Plural}' => $plural,
      ]);

      // Create the folder if it doesn't exist
      if (!is_dir('plugin/CustomPostTypes')) {
        mkdir('plugin/CustomPostTypes', 0777, true);
      }

      file_put_contents("plugin/CustomPostTypes/{$filename}", $content);

      $this->line(" Created plugin/CustomPostTypes/{$filename}");

      $this->info(
        "Remember to add {$className} in the config/plugin.php array in the 'custom_post_types' key."
      );
    }

    /**
     * Create a Shortcode controller
     *
     * @param string|null $className The class name
     */
    protected function createShortcode(?string $className = '')
    {
      if ($this->isHelp($className)) {
        $this->info('Use php bones make:shortcode <ClassName>');

        return;
      }

      // ask className if empty
      $className = $this->askClassNameIfEmpty($className);

      $filename = sprintf('%s.php', $className);

      // current plugin name and namespace
      $namespace = $this->getNamespace();

      // stubbing
      $content = $this->prepareStub('shortcode', [
        '{Namespace}' => $namespace,
        '{ClassName}' => $className,
      ]);

      // Create the folder if it doesn't exist
      if (!is_dir('plugin/Shortcodes')) {
        mkdir('plugin/Shortcodes', 0777, true);
      }

      file_put_contents("plugin/Shortcodes/{$filename}", $content);

      $this->line(" Created plugin/Shortcodes/{$filename}");

      $this->info(
        "Remember to add {$className} in the config/plugin.php array in the 'shortcodes' key."
      );
    }

    /**
     * Create a Schedule controller
     *
     * @since 1.8.0
     *
     * @param string|null $className The class name
     */
    protected function createSchedule(?string $className = '')
    {
      if ($this->isHelp($className)) {
        $this->info('Use php bones make:schedule <ClassName>');

        return;
      }

      // ask className if empty
      $className = $this->askClassNameIfEmpty($className);

      $filename = sprintf('%s.php', $className);

      // current plugin name and namespace
      $namespace = $this->getNamespace();

      // stubbing
      $content = $this->prepareStub('schedule', [
        '{Namespace}' => $namespace,
        '{ClassName}' => $className,
      ]);

      // Create the folder if it doesn't exist
      if (!is_dir('plugin/Providers')) {
        mkdir('plugin/Providers', 0777, true);
      }

      file_put_contents("plugin/Providers/{$filename}", $content);

      $this->line(" Created plugin/Providers/{$filename}");

      $this->info(
        "Remember to add {$className} in the config/plugin.php array in the 'providers' key."
      );
    }

    /**
     * Create a Service Provider
     *
     * @param string|null $className The class name
     */
    protected function createProvider(?string $className = '')
    {
      if ($this->isHelp($className)) {
        $this->info('Use php bones make:provider <ClassName>');

        return;
      }

      // ask className if empty
      $className = $this->askClassNameIfEmpty($className);

      $filename = sprintf('%s.php', $className);

      // current plugin name and namespace
      $namespace = $this->getNamespace();

      // stubbing
      $content = $this->prepareStub('provider', [
        '{Namespace}' => $namespace,
        '{ClassName}' => $className,
      ]);

      // Create the folder if it doesn't exist
      if (!is_dir('plugin/Providers')) {
        mkdir('plugin/Providers', 0777, true);
      }

      file_put_contents("plugin/Providers/{$filename}", $content);

      $this->line(" Created plugin/Providers/{$filename}");
    }

    /**
     * Create a Ajax controller
     *
     * @param string|null $className The class name
     */
    protected function createAjax(?string $className = '')
    {
      if ($this->isHelp($className)) {
        $this->info('Use php bones make:ajax <ClassName>');

        return;
      }

      // ask className if empty
      $className = $this->askClassNameIfEmpty($className);

      // current plugin name and namespace
      $namespace = $this->getNamespace();

      // stubbing
      $content = $this->prepareStub('ajax', [
        '{Namespace}' => $namespace,
        '{ClassName}' => $className,
      ]);

      // Create the folder if it doesn't exist
      if (!is_dir('plugin/Ajax')) {
        mkdir('plugin/Ajax', 0777, true);
      }

      $filename = sprintf('%s.php', $className);

      file_put_contents("plugin/Ajax/{$filename}", $content);

      $this->line(" Created plugin/Ajax/{$filename}");

      $this->info(
        "Remember to add {$className} in the config/plugin.php array in the 'ajax' key."
      );
    }

    /**
     * Create a Custom Taxonomy controller
     *
     * @param string|null $className The class name
     */
    protected function createCustomTaxonomyType(?string $className = '')
    {
      if ($this->isHelp($className)) {
        $this->info('Use php bones make:ctt <ClassName>');

        return;
      }

      // ask className if empty
      $className = $this->askClassNameIfEmpty($className);

      $filename = sprintf('%s.php', $className);

      // current plugin name and namespace
      $namespace = $this->getNamespace();

      $slug = $this->getPluginId();

      $id = $this->ask('Enter a ID:', $slug);
      $name = $this->ask('Enter the name:');
      $plural = $this->ask('Enter the plural name:');

      $this->line(
        'The object type below refers to the id of your previous Custom Post Type'
      );

      $objectType = $this->ask('Enter the object type to bound:');

      if (empty($id)) {
        $id = $slug;
      }

      // stubbing
      $content = $this->prepareStub('ctt', [
        '{Namespace}' => $namespace,
        '{ClassName}' => $className,
        '{ID}' => $id,
        '{Name}' => $name,
        '{Plural}' => $plural,
        '{ObjectType}' => $objectType,
      ]);

      // Create the folder if it doesn't exist
      if (!is_dir('plugin/CustomTaxonomyTypes')) {
        mkdir('plugin/CustomTaxonomyTypes', 0777, true);
      }

      file_put_contents("plugin/CustomTaxonomyTypes/{$filename}", $content);

      $this->line(" Created plugin/CustomTaxonomyTypes/{$filename}");

      $this->info(
        "Remember to add {$className} in the config/plugin.php array in the 'custom_taxonomy_types' key."
      );
    }

    /**
     * Create a Widget controller
     *
     * @param string|null $className The class name
     */
    protected function createWidget(?string $className = '')
    {
      if ($this->isHelp($className)) {
        $this->info('Use php bones make:widget <ClassName>');

        return;
      }

      // ask className if empty
      $className = $this->askClassNameIfEmpty($className);

      $filename = sprintf('%s.php', $className);

      // current plugin name and namespace
      [$pluginName, $namespace] = $this->getPluginNameAndNamespace();

      $slug = $this->getPluginId();

      // stubbing
      $content = $this->prepareStub('widget', [
        '{Namespace}' => $namespace,
        '{ClassName}' => $className,
        '{PluginName}' => $pluginName,
        '{Slug}' => $slug,
      ]);

      // Create the folder if it doesn't exist
      if (!is_dir('plugin/Widgets')) {
        mkdir('plugin/Widgets', 0777, true);
      }

      file_put_contents("plugin/Widgets/{$filename}", $content);

      $this->line(" Created plugin/Widgets/{$filename}");

      if (!is_dir('resources/views/widgets')) {
        mkdir('resources/views/widgets', 0777, true);
      }

      file_put_contents(
        "resources/views/widgets/{$slug}-form.php",
        '<h2>Backend form</h2>'
      );
      file_put_contents(
        "resources/views/widgets/{$slug}-index.php",
        '<h2>Frontend Widget output</h2>'
      );

      $this->line(" Created resources/views/widgets/{$slug}-form.php");
      $this->line(" Created resources/views/widgets/{$slug}-index.php");

      $this->info(
        "Remember to add {$className} in the config/plugin.php array in the 'widgets' key."
      );
    }

    /**
     * Create a database Model
     *
     * @param string|null $className The class name
     */
    protected function createModel(?string $className = '')
    {
      if ($this->isHelp($className)) {
        $this->info('Use php bones make:model <ClassName>');

        return;
      }

      // ask className if empty
      $className = $this->askClassNameIfEmpty($className);

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

      // stubbing
      $content = $this->prepareStub('model', [
        '{Namespace}' => $namespace,
        '{ClassName}' => $className,
      ]);

      if (!empty($path)) {
        $content = str_replace('{Path}', $namespacePath, $content);
        mkdir("plugin/Models/{$path}", 0777, true);
      } else {
        $content = str_replace('{Path}', '', $content);
      }

      $filename = sprintf('%s.php', $className);

      file_put_contents("plugin/Models/{$path}{$filename}", $content);

      $this->line(" Created plugin/Models/{$path}{$filename}");

      $this->optimize();
    }

    /**
     * Create an Eloquent database Model
     *
     * @param string|null $className The class name
     */
    protected function createEloquentModel(?string $className = '')
    {
      if ($this->isHelp($className)) {
        $this->info('Use php bones make:eloquent-model <ClassName>');

        return;
      }

      // ask className if empty
      $className = $this->askClassNameIfEmpty($className);

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
      $table = strtolower($className);

      // stubbing
      $content = $this->prepareStub('eloquent-model', [
        '{Namespace}' => $namespace,
        '{ClassName}' => $className,
        '{Table}' => $table,
      ]);

      if (!empty($path)) {
        $content = str_replace('{Path}', $namespacePath, $content);
        mkdir("plugin/Models/{$path}", 0777, true);
      } else {
        $content = str_replace('{Path}', '', $content);
      }

      $filename = sprintf('%s.php', $className);

      file_put_contents("plugin/Models/{$path}{$filename}", $content);

      $this->line(" Created plugin/Models/{$path}{$filename}");

      $this->optimize();
    }

    /**
     * Create an API Controller
     *
     * @param string|null $className The class name
     */
    protected function createAPIController(?string $className = '')
    {
      if ($this->isHelp($className)) {
        $this->info('Use php bones make:api <ClassName>');

        return;
      }

      // ask className if empty
      $className = $this->askClassNameIfEmpty($className);

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

      // stubbing
      $content = $this->prepareStub('api', [
        '{Namespace}' => $namespace,
        '{ClassName}' => $className,
      ]);

      // Create the folder if it doesn't exist
      if (!is_dir('plugin/API')) {
        mkdir('plugin/API', 0777, true);
      }

      if (!empty($path)) {
        $content = str_replace('{Path}', $namespacePath, $content);
        mkdir("plugin/API/{$path}", 0777, true);
      } else {
        $content = str_replace('{Path}', '', $content);
      }

      $filename = sprintf('%s.php', $className);

      file_put_contents("plugin/API/{$path}{$filename}", $content);

      $this->line(" Created plugin/API/{$path}{$filename}");

      $this->optimize();
    }

    /**
     * Let's roll
     *
     * @return BonesCommandLine
     */
    public static function run(): BonesCommandLine
    {
      return new self();
    }
  }

  BonesCommandLine::run();
}
