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

      $preReleases1 = array_pad($v1preReleaseParts, count($v2preReleaseParts), null);
      $preReleases2 = array_pad($v2preReleaseParts, count($v1preReleaseParts), null);

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

      array_push($identifiers, (string) ((int) array_pop($identifiers) + 1));

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
        throw new InvalidVersionException('Invalid semantic version string provided');
      }

      $this->major = (int) $matches['major'];
      $this->minor = (int) $matches['minor'];
      $this->patch = (int) $matches['patch'];
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
      return $prefix . (string) $this;
    }
  }
}

namespace Bones\Traits {
  use Exception;

  // Standard Color Definitions
  define('WPBONES_COLOR_BLACK', "\033[0;30m");
  define('WPBONES_COLOR_RED', "\033[0;31m");
  define('WPBONES_COLOR_GREEN', "\033[0;32m");
  define('WPBONES_COLOR_YELLOW', "\033[0;33m");
  define('WPBONES_COLOR_BLUE', "\033[0;34m");
  define('WPBONES_COLOR_MAGENTA', "\033[0;35m");
  define('WPBONES_COLOR_CYAN', "\033[0;36m");
  define('WPBONES_COLOR_WHITE', "\033[0;37m");

  // Definition of bold colors
  define('WPBONES_COLOR_BOLD_BLACK', "\033[1;30m");
  define('WPBONES_COLOR_BOLD_RED', "\033[1;31m");
  define('WPBONES_COLOR_BOLD_GREEN', "\033[1;32m");
  define('WPBONES_COLOR_BOLD_YELLOW', "\033[1;33m");
  define('WPBONES_COLOR_BOLD_BLUE', "\033[1;34m");
  define('WPBONES_COLOR_BOLD_MAGENTA', "\033[1;35m");
  define('WPBONES_COLOR_BOLD_CYAN', "\033[1;36m");
  define('WPBONES_COLOR_BOLD_WHITE', "\033[1;37m");

  // Definition of Light Colors
  define('WPBONES_COLOR_LIGHT_BLACK', "\033[0;38;5;240m");
  define('WPBONES_COLOR_LIGHT_RED', "\033[0;38;5;203m");
  define('WPBONES_COLOR_LIGHT_GREEN', "\033[0;38;5;82m");
  define('WPBONES_COLOR_LIGHT_YELLOW', "\033[0;38;5;227m");
  define('WPBONES_COLOR_LIGHT_BLUE', "\033[0;38;5;117m");
  define('WPBONES_COLOR_LIGHT_MAGENTA', "\033[0;38;5;213m");
  define('WPBONES_COLOR_LIGHT_CYAN', "\033[0;38;5;159m");
  define('WPBONES_COLOR_LIGHT_WHITE', "\033[0;38;5;15m");

  // Definition for color reset
  define('WPBONES_COLOR_RESET', "\033[0m");

  // Definition for cursor up
  define('WPBONES_CURSOR_UP', "\033[1A");

  trait Console
  {
    /** Set by ask() once stdin has ended: no answer will ever come. */
    protected bool $inputEnded = false;

    /**
     * Commodity to display a message in the console with color.
     *
     * @param string $str The message to display.
     * @param string $color The color to use. Default is 'white'.
     */
    protected function color($str, $color = WPBONES_COLOR_YELLOW)
    {
      echo $color . $str . WPBONES_COLOR_RESET;
    }

    /**
     * Commodity to display a message in the console.
     *
     * @param string $str The message to display.
     * @param bool $newLine Optional. Whether to add a new line at the end.
     */
    protected function info(string $str, $newLine = true)
    {
      $this->color($str, WPBONES_COLOR_LIGHT_MAGENTA);
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
      $this->color($str, WPBONES_COLOR_LIGHT_GREEN);
      echo $newLine ? "\n" : '';
    }

    /* Commodity to display an error message in the console, on STDERR since 2.0.10. */
    protected function error(string $str, $newLine = true)
    {
      fwrite(STDERR, '❌ ' . WPBONES_COLOR_BOLD_RED . $str . WPBONES_COLOR_RESET . ($newLine ? "\n" : ''));
    }

    /* Commodity to display an info message in the console. */
    protected function warning(string $str, $newLine = true)
    {
      echo '❗ ';
      $this->color($str, WPBONES_COLOR_BOLD_YELLOW);
      echo $newLine ? "\n" : '';
    }

    /* Commodity to display a success message in the console. */
    protected function success($str)
    {
      $this->color('✅ ' . $str, WPBONES_COLOR_GREEN);
    }

    protected function startCommand($str)
    {
      $this->color('------------------------------------------------' . "\n", WPBONES_COLOR_BLUE);
      $this->color('🙌 ' . $str . ' ' . $this->getPluginName() . "\n", WPBONES_COLOR_BLUE);
      $this->color('------------------------------------------------' . "\n", WPBONES_COLOR_BLUE);
    }

    /* Commodity to display a start progress message in the console. */
    protected function startProgress($str)
    {
      $this->color('🚧 ' . $str . "\n", WPBONES_COLOR_GREEN);
    }

    /* Commodity to replace a previous progress message in the console with cursor up. */
    protected function endProgress()
    {
      echo WPBONES_CURSOR_UP . WPBONES_COLOR_GREEN . '✅' . WPBONES_COLOR_RESET . "\n";
    }

    /* Commodity to display a completed message in the console. */
    protected function processCompleted($str)
    {
      $this->color('✅ ' . $str . "\n", WPBONES_COLOR_GREEN);
    }

    /**
     * Get input from console
     *
     * @param string $str The question to ask
     * @param string|null $default The default value
     */
    protected function ask(string $str, ?string $default = ''): string
    {
      $str =
        WPBONES_COLOR_GREEN . "❓ $str" . (empty($default) ? ': ' : " (default: {$default}): ") . WPBONES_COLOR_RESET;

      // Use readline to get the user input
      $line = readline($str);

      // false is the end of input (Ctrl-D, or a pipe that ran dry): the default answers, and the
      // caller can tell that nobody will ever answer a question asked in a loop.
      if ($line === false) {
        $this->inputEnded = true;
        echo "\n";
      }

      // Trim the input to remove extra spaces or newlines
      $line = trim((string) $line);

      // A null default used to come back as null from a function declared to return string.
      return $line !== '' ? $line : (string) $default;
    }

    /**
     * Get an option value from the command line arguments
     *
     * @param array $argv The command line arguments
     * @param string $prefix The prefix to search for
     * @param string $default Optional. The default value
     *
     * @since 1.9.2
     */
    protected function getOptionValue($argv, $prefix, $default = '')
    {
      $result = $default;
      foreach ($argv as $element) {
        if (strpos($element, $prefix) === 0) {
          $result = substr($element, strlen($prefix));
          break;
        }
      }
      return $result;
    }

    /**
     * Remove values from an array
     *
     * @param array $argv The array to remove values from
     * @param array $values The values to remove
     *
     * @since 1.9.2
     */
    protected function removeValues(array $argv, array $values): array
    {
      return array_values(array_diff($argv, $values));
    }
  }

  /**
   * WordPress related functionality
   *
   * @since 1.9.2
   */
  trait WordPress
  {
    use Console;

    // WordPress loaded flag
    protected $wpLoaded = false;

    /* The plugin folder as the shell spells it, symlinks included: see BonesCommandLine. */
    abstract protected function pluginPathAsTyped(): string;

    /* Protected version of the do_action function */
    protected function do_action(...$args)
    {
      // Check if WordPress is loaded
      if (!defined('ABSPATH') || !function_exists('do_action')) {
        return;
      }

      do_action(...$args);
    }

    /* Protected version of the apply_filters function */
    protected function apply_filters(...$args)
    {
      // Check if WordPress is loaded
      if (!defined('ABSPATH') || !function_exists('apply_filters')) {
        // return the second argument
        return $args[1];
      }

      return apply_filters(...$args);
    }

    /** Load Composer autoloader */
    protected function loadComposerAutoloader()
    {
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
        $this->error("Error! Can't load Composer autoload (" . $e->getMessage() . ')');
        exit();
      }
    }

    /* Load WordPress core and all environment. */
    protected function loadWordPress()
    {
      try {
        // We have to load the WordPress environment.
        $currentDir = $this->pluginPathAsTyped();
        $wpLoadPath = dirname(dirname(dirname($currentDir))) . '/wp-load.php';

        if (!file_exists($wpLoadPath)) {
          $this->wpLoaded = false;
          return;
        }

        require $wpLoadPath;
        $this->wpLoaded = true;
      } catch (Exception $e) {
        $this->error("Error! Can't load WordPress (" . $e->getMessage() . ')');
      }

      $this->loadComposerAutoloader();

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
        $this->error("Error! Can't load the plugin env (" . $e->getMessage() . ')');
        exit();
      }
    }
  }
}

/**
 * Bones
 *
 * @package Bones
 */

namespace Bones {
  /* The minimum PHP version required to run Bones: keep it equal to composer.json's "php". */
  define('WPBONES_MINIMAL_PHP_VERSION', '8.1');

  /* MARK: The WP Bones command line version. */
  define('WPBONES_COMMAND_LINE_VERSION', '3.1.0');

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
    echo WPBONES_COLOR_BOLD_RED .
      '❌ Error! You must run with PHP version ' .
      WPBONES_MINIMAL_PHP_VERSION .
      " or greater\n" .
      WPBONES_COLOR_RESET;
    exit();
  }

  /**
   * @class BonesCommandLine
   */
  class BonesCommandLine
  {
    use Traits\Console;
    use Traits\WordPress;

    /**
     * WP Bones version
     */
    const VERSION = WPBONES_COMMAND_LINE_VERSION;

    /**
     * Where the framework's stubs are, relative to the plugin root.
     *
     * @since 3.1.0
     */
    const FRAMEWORK_STUBS = 'vendor/wpbones/wpbones/src/Console/stubs';

    /**
     * Where a plugin keeps its own stubs, which `make:*` prefers to the framework's.
     *
     * @since 3.1.0
     */
    const PLUGIN_STUBS = 'stubs';

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

    /**
     * The folder bones was run from, before it moved into the plugin.
     *
     * @since 2.1.0
     */
    protected string $invokedFrom = '';

    /**
     * The plugin folder as the shell spells it: see pluginPathAsTyped().
     *
     * @since 2.1.0
     */
    protected ?string $pluginPath = null;

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
      // Up to 2.0.12 half of the paths were the shell's and half the plugin's, so run from
      // anywhere but the plugin root bones could not read `namespace` and died on a TypeError.
      // Every relative path now means the plugin; a path typed on the command line still means
      // what the shell meant (pathFromCommandLine()).
      $this->invokedFrom = getcwd() ?: __DIR__;
      $plugin = $this->pluginPathAsTyped();
      chdir(__DIR__);

      // What a shell's `cd` into the plugin leaves behind. Command::loadWordPress(), which custom
      // commands use, looks for WordPress and vendor/autoload.php through PWD, and so may anything
      // bones runs.
      $_SERVER['PWD'] = $plugin;
      putenv("PWD={$plugin}");

      $arguments = $this->arguments();

      // Load the console kernel — skip for `rename`, which is the bootstrap
      // command that brings vendor/plugin namespaces into sync. Loading the
      // kernel here would resolve the plugin's Console\Kernel parent class
      // against the freshly reinstalled vendor (still on the default
      // `WPKirk\WPBones\...` prefix) and fatal with "class not found".
      if (!in_array('rename', $arguments, true)) {
        $this->loadKernel();
      }

      // Check WP-CLI
      $output = shell_exec('wp --info 2>&1');
      if (strpos($output, 'WP-CLI version') !== false) {
        preg_match('/WP-CLI version:\s+([\d\.]+)/', $output, $matches);
        $this->wpCliVersion = $matches[1] ?? null;
      }

      // Start
      if (empty($arguments) || $this->isCommand('--help')) {
        $this->help();
      }
      // Command needs WordPress to work
      //
      // tinker
      elseif ($this->isCommand('tinker')) {
        if (!$this->isHelp()) {
          $this->loadWordPress();
        }
        $this->tinker();
      }
      // deploy
      elseif ($this->isCommand('deploy')) {
        if (!$this->isHelp()) {
          $this->loadWordPress();
        }
        $this->deploy($this->getCommandParams());
      }
      // migrate, migrate:status
      elseif ($this->isCommand('migrate') || $this->isCommand('migrate:status')) {
        if (!$this->isHelp()) {
          $this->loadWordPress();
        }
        $this->isCommand('migrate') ? $this->migrate() : $this->migrateStatus();
      }

      // go ahead...and run the command need workpress
      else {
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

    /** Check and load for console kernel extensions. */
    protected function loadKernel()
    {
      // Check if there is an custom console kernel
      if (file_exists(__DIR__ . '/plugin/Console/Kernel.php')) {
        // current plugin name and namespace
        $namespace = $this->getNamespace();

        $vendor = __DIR__ . '/vendor/wpbones/wpbones/src';
        include_once $vendor . '/Support/Str.php';
        include_once $vendor . '/Support/Traits/HasAttributes.php';
        include_once $vendor . '/Foundation/Console/Kernel.php';
        include_once $vendor . '/Console/Command.php';
        include_once __DIR__ . '/plugin/Console/Kernel.php';

        // include all any classes in the folder /plugin/Console/Commands/
        $commands = glob(__DIR__ . '/plugin/Console/Commands/*.php');
        foreach ($commands as $command) {
          include_once $command;
        }

        $kernelClass = "{$namespace}\\Console\\Kernel";
        $this->kernel = new $kernelClass();
      }
    }

    /**
     * Commodity function to check if ClassName has been requested.
     *
     * @param string|null $className Optional. Command to check.
     *
     * @return string
     */
    protected function askClassNameIfEmpty($className = ''): string
    {
      if (empty($className)) {
        $className = $this->ask('ClassName');
        if (empty($className)) {
          $this->error('ClassName is required');
          exit(1);
        }
      }

      return $className;
    }

    /**
     * The first argument after the command that is not an option, e.g. the class name in
     * `make:controller --force Shop/Cart`.
     *
     * @since 2.0.10
     */
    protected function getCommandArgument(): ?string
    {
      foreach ($this->getCommandParams() ?? [] as $param) {
        if (strpos((string) $param, '--') !== 0) {
          return $param;
        }
      }

      return null;
    }

    /**
     * Whether an option such as `--force` was passed after the command.
     *
     * @since 2.0.10
     */
    protected function hasOption(string $option): bool
    {
      return in_array($option, $this->getCommandParams() ?? [], true);
    }

    /**
     * Refuse a class name that is not a PHP class, or a path that climbs out of the folder.
     *
     * Up to 2.0.9 the name was used as it came: `../../Escaped` wrote outside the target
     * folder and `my-model` wrote a file that does not parse.
     *
     * @since 2.0.10
     * @param string $className    `Name`, or `Folder/Name` when $allowFolders is true.
     * @param bool   $allowFolders Whether `Folder/` segments are accepted.
     */
    protected function validateClassName(string $className, bool $allowFolders = true): string
    {
      $segments = explode('/', $className);

      if (!$allowFolders && count($segments) > 1) {
        $this->error("'{$className}': this command does not take a folder, only a class name.");
        exit(1);
      }

      foreach ($segments as $segment) {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $segment)) {
          $this->error(
            "'{$className}' is not a valid class name: use letters, digits and underscores, not starting with a digit" .
              ($allowFolders ? ', and "/" between folders.' : '.'),
          );
          exit(1);
        }
      }

      return $className;
    }

    /**
     * Write a file generated by a `make:*` command, creating its folder.
     *
     * Up to 2.0.9 every generator overwrote an existing file without asking, and reported
     * "Created" even when the write had failed.
     *
     * @since 2.0.10
     */
    protected function writeGeneratedFile(string $file, string $content): void
    {
      if (file_exists($file) && !$this->hasOption('--force')) {
        $this->error("{$file} already exists, nothing was written. Pass --force to overwrite it.");
        exit(1);
      }

      $this->mkdirIfNotExists(dirname($file));

      if (file_put_contents($file, $content) === false) {
        $this->error("Could not write {$file}.");
        exit(1);
      }

      $this->line(" Created {$file}");
    }

    /**
     * Run a shell command with its output shown as it happens, and return its exit status.
     *
     * Up to 2.0.9 these ran in backticks: nothing appeared until the command ended, the exit
     * status was lost, and a command that wrote only to stderr handed line() a null, which is
     * a TypeError (composer dump-autoload in a plugin without post-autoload-dump scripts).
     *
     * @since 2.0.10
     */
    protected function runShell(string $command): int
    {
      $status = 0;
      passthru($command, $status);

      return $status;
    }

    /**
     * An absolute path with "." and ".." resolved, and symlinks followed for the part that
     * already exists, so that two spellings of one folder compare equal.
     *
     * @since 2.0.10
     */
    protected function resolvePath(string $path): string
    {
      // On Windows "\foo" is the root of the current drive, not a path without one: give it the
      // drive, or it compares as a folder that contains nothing (Codex review of #111).
      if (PHP_OS_FAMILY === 'Windows' && preg_match('#^[\\\\/](?![\\\\/])#', $path)) {
        $path = substr((string) getcwd(), 0, 2) . $path;
      }

      if (!preg_match('#^([A-Za-z]:)?[\\\\/]#', $path)) {
        $path = getcwd() . DIRECTORY_SEPARATOR . $path;
      }

      $segments = preg_split('#[\\\\/]+#', $path, -1, PREG_SPLIT_NO_EMPTY);
      $drive = isset($segments[0]) && preg_match('/^[A-Za-z]:$/', $segments[0]) ? strtoupper(array_shift($segments)) : '';

      // One segment at a time, the way the filesystem does it: a symlink is followed BEFORE the
      // ".." after it applies, so `out/alias/..` climbs from wherever alias points. Collapsing
      // ".." first checked a different folder from the one deleteDirectory() would empty.
      $current = $drive . DIRECTORY_SEPARATOR;
      $exists = true;

      foreach ($segments as $segment) {
        if ($segment === '.') {
          continue;
        }

        if ($segment === '..') {
          $current = dirname($current);
          continue;
        }

        $next = rtrim($current, '\\/') . DIRECTORY_SEPARATOR . $segment;

        if ($exists && ($real = realpath($next)) !== false) {
          $current = $real;
          continue;
        }

        // Past the part that exists there is no link left to follow.
        $exists = false;
        $current = $next;
      }

      return $current;
    }

    /**
     * A path typed on the command line, as the shell meant it.
     *
     * bones works from the plugin folder since 2.1.0, so a relative path typed somewhere else is
     * anchored to the folder it was typed in. Typed from the plugin root it comes back as it was.
     *
     * @since 2.1.0
     */
    protected function pathFromCommandLine(string $path): string
    {
      if ($path === '' || $this->resolvePath($this->invokedFrom) === $this->resolvePath(__DIR__)) {
        return $path;
      }

      // On Windows "\foo" is the root of the drive it was typed on.
      if (PHP_OS_FAMILY === 'Windows' && preg_match('#^[\\\\/](?![\\\\/])#', $path)) {
        return substr($this->invokedFrom, 0, 2) . $path;
      }

      if (preg_match('#^([A-Za-z]:)?[\\\\/]#', $path)) {
        return $path;
      }

      return rtrim($this->invokedFrom, '\\/') . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * The plugin folder as the shell spells it, symlinks included.
     *
     * A plugin developed elsewhere and symlinked into wp-content/plugins reaches WordPress only
     * through that spelling: __DIR__ is the physical folder. Up to 2.0.12 the shell's PWD stood
     * for it, which is right only when bones is run from the plugin root.
     *
     * @since 2.1.0
     */
    protected function pluginPathAsTyped(): string
    {
      // Worked out once, in boot(), before PWD is made to name the plugin.
      if ($this->pluginPath !== null) {
        return $this->pluginPath;
      }

      $pwd = (string) ($_SERVER['PWD'] ?? getenv('PWD'));
      $base = $pwd !== '' && realpath($pwd) === realpath($this->invokedFrom) ? $pwd : $this->invokedFrom;
      $script = (string) ($_SERVER['argv'][0] ?? 'bones');
      $folder = preg_match('#^([A-Za-z]:)?[\\\\/]#', $script)
        ? dirname($script)
        : $base . DIRECTORY_SEPARATOR . dirname($script);

      // "." and ".." as written, without following links: following them is what realpath() does.
      $segments = [];
      foreach (preg_split('#[\\\\/]+#', $folder) as $index => $segment) {
        if ($segment === '.' || ($segment === '' && $index > 0)) {
          continue;
        }

        if ($segment === '..' && count($segments) > 1) {
          array_pop($segments);
          continue;
        }

        $segments[] = $segment;
      }

      $typed = implode(DIRECTORY_SEPARATOR, $segments);

      return $this->pluginPath = $typed !== '' && realpath($typed) === realpath(__DIR__) ? $typed : __DIR__;
    }

    /**
     * Refuse a deploy destination that would destroy something.
     *
     * Up to 2.0.9 an existing destination was deleted after a five-second pause and nothing
     * else: `deploy ..` removed every plugin beside this one and the plugin itself, and
     * `deploy build` copied the plugin into its own subfolder until the path was too long.
     *
     * The plugin, its parents and anything inside it are refused whatever the flags. An existing
     * folder is replaced when it is empty or holds a previous deploy of this plugin (its main
     * file, with this plugin's name); anything else needs --force.
     *
     * @since 2.0.10
     */
    protected function assertSafeDeployDestination(string $path, bool $force): void
    {
      $source = $this->resolvePath(__DIR__);
      $target = $this->resolvePath($path);
      $sep = DIRECTORY_SEPARATOR;

      // Windows paths are case-insensitive: C:\Plugin and c:\plugin are one folder.
      if (PHP_OS_FAMILY === 'Windows') {
        $source = strtolower($source);
        $target = strtolower($target);
      }

      if ($target === $source) {
        $this->error("Deploy refused: '{$path}' is the plugin itself.");
        exit(1);
      }

      if (strpos($source . $sep, rtrim($target, '\\/') . $sep) === 0) {
        $this->error("Deploy refused: '{$path}' contains the plugin, and deploying there would delete it.");
        exit(1);
      }

      if (strpos($target, $source . $sep) === 0) {
        $this->error("Deploy refused: '{$path}' is inside the plugin, which would be copied into itself.");
        exit(1);
      }

      if ($force || !is_dir($target) || $this->isPreviousDeploy($target)) {
        return;
      }

      $this->error(
        "Deploy refused: '{$path}' is not empty and is not a previous deploy of this plugin, so it was left alone. Pass --force to replace it.",
      );
      exit(1);
    }

    /**
     * Whether a folder is empty, or holds a deploy of this plugin: its main file, declaring the
     * same Plugin Name.
     *
     * @since 2.0.10
     */
    protected function isPreviousDeploy(string $folder): bool
    {
      if (count(scandir($folder) ?: []) <= 2) {
        return true;
      }

      $mainFile = $folder . DIRECTORY_SEPARATOR . $this->getMainPluginFile();

      if (!is_file($mainFile)) {
        return false;
      }

      $ours = $this->extractPluginHeaderInfo('Plugin Name')['Plugin Name'] ?? null;
      $theirs = preg_match('/^[ \t\/*#@]*Plugin Name:[ \t]*(.+)$/m', (string) file_get_contents($mainFile, false, null, 0, 8192), $matches)
        ? trim($matches[1])
        : null;

      return $ours !== null && $ours === $theirs;
    }

    /**
     * Ask the user which package manager to use
     *
     * @since 1.9.0
     * @return string|null The name of the package manager to use
     */
    protected function askPackageManager()
    {
      $packageManager = null;

      // get the available package manager
      $listPackageManagers = $this->getListAvailablePackageManagers();

      if ($listPackageManagers) {
        // if we found just one package manager, we use it
        if (count($listPackageManagers) === 1) {
          return $listPackageManagers[0];
        }

        $availablePackageManagerStr = implode(', ', $listPackageManagers);
        $this->info('🔍 Found package managers: ' . $availablePackageManagerStr);

        // check if "npm" is available and use it by default
        if (in_array('npm', $listPackageManagers)) {
          $packageManager = 'npm';
        }

        // ask which package manager to use
        $packageManager = $this->ask(
          'Which package manager do you want to use (' . $availablePackageManagerStr . ')',
          $packageManager,
        );
      }

      return $packageManager;
    }

    /**
     * Detect the package manager used by the current plugin, based on the
     * lockfile present in the plugin root. Unlike askPackageManager(), this
     * inspects the *project* state, not what is installed on the host, so
     * messages we print stay consistent with how the developer actually runs
     * their plugin.
     *
     * Detection order: yarn.lock → pnpm-lock.yaml → package-lock.json.
     * If no lockfile is found we default to yarn (v2 boilerplates are
     * yarn-first and ship yarn.lock).
     *
     * @since 2.0.2
     * @return string One of 'yarn', 'pnpm', 'npm'.
     */
    protected function detectProjectPackageManager(): string
    {
      if (file_exists('yarn.lock')) {
        return 'yarn';
      }
      if (file_exists('pnpm-lock.yaml')) {
        return 'pnpm';
      }
      if (file_exists('package-lock.json')) {
        return 'npm';
      }
      return 'yarn';
    }

    /**
     * Format a package-manager command string (e.g. "yarn dev", "npm run dev",
     * "pnpm dev"). npm requires `run` for any script that is not a built-in
     * lifecycle verb; yarn and pnpm accept the bare script name.
     *
     * @since 2.0.2
     * @param string $pm     Package manager: 'yarn', 'pnpm' or 'npm'.
     * @param string $script Script name (e.g. 'dev', 'build', 'install').
     * @return string
     */
    protected function packageManagerCommand(string $pm, string $script): string
    {
      if ($pm === 'npm') {
        $lifecycle = ['install', 'start', 'test', 'restart', 'stop', 'ci'];
        if (in_array($script, $lifecycle, true)) {
          return "npm {$script}";
        }
        return "npm run {$script}";
      }
      return "{$pm} {$script}";
    }

    /**
     * Return the params after "php bones [command]".
     *
     * @param int|null $index Optional. Index of param.
     *                   If NULL will be returned the whole array.
     *
     * @return array|string
     */
    protected function getCommandParams($index = null)
    {
      $params = $this->arguments();

      // strip the command name
      array_shift($params);

      return !is_null($index) ? $params[$index] ?? null : $params;
    }

    /**
     * Return the path from the asked class.
     *
     * @param string $className Class name.
     *
     * @since 1.9.2
     *
     * @return array
     */
    protected function getPathFromAskedClass($className)
    {
      // get additional path
      $path = $namespacePath = '';
      if (false !== strpos($className, '/')) {
        $parts = explode('/', $className);
        $className = array_pop($parts);
        $path = implode('/', $parts) . '/';
        $namespacePath = '\\' . implode('\\', $parts);
      }
      return [$path, $namespacePath, $className];
    }

    /* Return the default plugin name and namespace. */
    protected function getDefaultPluginNameAndNamespace(): array
    {
      return ['WP Kirk', 'WPKirk'];
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
     * Returns the first package manager available.
     *
     * @return string|null The name of the available package manager (yarn, npm, pnpm, bun) or null if none is available.
     */
    protected function getAvailablePackageManager(): ?string
    {
      $packageManagers = $this->getListAvailablePackageManagers();

      if (empty($packageManagers)) {
        return null;
      }

      return $packageManagers[0];
    }

    /**
     * Returns the list of effective package managers.
     * We will check for ['yarn', 'npm', 'pnpm', 'bun'] and return the available ones.
     *
     * @since 1.9.0
     * @return array The list of available package managers
     */
    protected function getListAvailablePackageManagers(): array
    {
      $checkIdAvailable = ['yarn', 'npm', 'pnpm', 'bun'];
      $available = [];

      foreach ($checkIdAvailable as $id) {
        if ($this->isCommandAvailable($id)) {
          $available[] = $id;
        }
      }

      return $available;
    }

    /**
     * The content of a stub: the plugin's own `stubs/{$filename}.stub` when there is one, the
     * framework's otherwise. A stub found nowhere stops the command before anything is written.
     *
     * Up to 3.0.x only the framework's stub was read, so the only way to change what `make:*`
     * writes was to edit vendor/, which the next `composer update` put back (#133).
     *
     * @since 3.1.0 The plugin's `stubs/` folder comes first.
     * @param string $filename The stub's name, without `.stub`.
     */
    public function getStubContent(string $filename): string
    {
      $own = self::PLUGIN_STUBS . "/{$filename}.stub";
      $file = is_file($own) ? $own : self::FRAMEWORK_STUBS . "/{$filename}.stub";
      $content = is_file($file) ? file_get_contents($file) : false;

      if ($content === false) {
        $this->error("Could not read the stub {$filename}.stub, neither from {$own} nor from the framework: nothing was written.");
        exit(1);
      }

      if ($file === $own) {
        $this->line(" Using {$own}");
      }

      return $content;
    }

    /**
     * Copy the framework's stubs into the plugin's `stubs/` folder, where `make:*` reads them
     * first: every stub, or the ones named. A stub already there is kept unless `--force`, since
     * it is usually one the team has customised.
     *
     * @since 3.1.0
     */
    protected function publishStubs(): void
    {
      if ($this->isHelp()) {
        $this->line("\nUsage:");
        $this->info("  stub:publish [<stub> ...] [--force]\n");
        $this->line('Copies the framework\'s stubs into ' . self::PLUGIN_STUBS . '/, where every make:* command reads them first.');
        $this->line('Without names it copies them all. A stub already there is kept: pass --force to replace it.');
        exit();
      }

      $available = [];

      foreach (glob(self::FRAMEWORK_STUBS . '/*.stub') ?: [] as $file) {
        $available[basename($file, '.stub')] = $file;
      }

      ksort($available);

      if ($available === []) {
        $this->error('No stubs found in ' . self::FRAMEWORK_STUBS . ': run composer install first.');
        exit(1);
      }

      // `controller` and `controller.stub` name the same stub.
      $names = array_values(
        array_map(
          fn($param) => preg_replace('/\.stub$/', '', (string) $param),
          array_filter($this->getCommandParams() ?? [], fn($param) => strpos((string) $param, '--') !== 0),
        ),
      );
      $unknown = array_diff($names, array_keys($available));

      if ($unknown !== []) {
        $this->error(
          'Unknown stub(s): ' . implode(', ', $unknown) . '. The stubs are: ' . implode(', ', array_keys($available)) . '. Nothing was written.',
        );
        exit(1);
      }

      $this->mkdirIfNotExists(self::PLUGIN_STUBS);

      foreach ($names === [] ? array_keys($available) : array_unique($names) as $name) {
        $target = self::PLUGIN_STUBS . "/{$name}.stub";

        if (file_exists($target) && !$this->hasOption('--force')) {
          $this->warning(" Kept {$target}, already there: pass --force to replace it");

          continue;
        }

        if (!copy($available[$name], $target)) {
          $this->error("Could not write {$target}.");
          exit(1);
        }

        $this->line(" Published {$target}");
      }
    }

    /**
     * The plugin's own stubs, which a deploy leaves out: development files, like the
     * framework's. Only the `.stub` files go, so a plugin that keeps something else in a
     * `stubs/` folder still ships it; a folder that holds nothing else goes whole, rather than
     * arriving empty.
     *
     * @since 3.1.0
     * @return string[] Entries of the deploy skip list.
     */
    protected function pluginStubsToSkip(): array
    {
      // The plugin root, as the deploy's skip list is relative to it.
      $folder = __DIR__ . '/' . self::PLUGIN_STUBS;

      if (!is_dir($folder)) {
        return [];
      }

      $stubs = [];
      $other = false;

      foreach (scandir($folder) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
          continue;
        }

        if (is_file("{$folder}/{$entry}") && substr($entry, -5) === '.stub') {
          $stubs[] = '/' . self::PLUGIN_STUBS . "/{$entry}";
        } elseif (strpos($entry, '.') !== 0) {
          $other = true;
        }
      }

      if ($stubs === []) {
        return [];
      }

      return $other ? $stubs : ['/' . self::PLUGIN_STUBS];
    }

    /**
     * Return true if the command is available in the system.
     *
     * @param string $command
     * @return bool
     */
    protected function isCommandAvailable(string $command): bool
    {
      $whereCommand = PHP_OS_FAMILY === 'Windows' ? 'where' : 'command -v';
      $output = shell_exec("$whereCommand $command");
      return !empty($output);
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

      $param = $this->getCommandParams()[0] ?? null;

      return !empty($param) && $param === '--help';
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

    /** Display the WP-CLI version in the bones console. */
    protected function wpCliInfoHelp()
    {
      if ($this->wpCliVersion) {
        $this->info("→ WP-CLI version: {$this->wpCliVersion}");
      } else {
        $this->warning("WP-CLI not found: we recommend to install it globally - https://wp-cli.org/#installing\n");
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
        [243, 242, 241, 240, 239, 238],
      ];
      $colorCode = $colorCodes[rand(0, count($colorCodes) - 1)];

      echo "\n\033[38;5;{$colorCode[0]}m██╗    ██╗██████╗     ██████╗  ██████╗ ███╗   ██╗███████╗███████╗\033[0m\n";
      echo "\033[38;5;{$colorCode[1]}m██║    ██║██╔══██╗    ██╔══██╗██╔═══██╗████╗  ██║██╔════╝██╔════╝\033[0m\n";
      echo "\033[38;5;{$colorCode[2]}m██║ █╗ ██║██████╔╝    ██████╔╝██║   ██║██╔██╗ ██║█████╗  ███████╗\033[0m\n";
      echo "\033[38;5;{$colorCode[3]}m██║███╗██║██╔═══╝     ██╔══██╗██║   ██║██║╚██╗██║██╔══╝  ╚════██║\033[0m\n";
      echo "\033[38;5;{$colorCode[4]}m╚███╔███╔╝██║         ██████╔╝╚██████╔╝██║ ╚████║███████╗███████║\033[0m\n";
      echo "\033[38;5;{$colorCode[5]}m ╚══╝╚══╝ ╚═╝         ╚═════╝  ╚═════╝ ╚═╝  ╚═══╝╚══════╝╚══════╝\033[0m\n";

      $this->line('                        Bones Version ' . self::VERSION . "\n");
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
      $this->line(' migrate                 Run the migrations that have not run on this site');
      $this->line(' migrate:create          Create a new Migration');
      $this->line(' migrate:status          List the migrations and whether each one ran');
      $this->line(' migrate:to-v2           Migrate gulp-based plugin to v2 webpack infrastructure');
      $this->line(' migrate:to-v3           Convert a 2.x plugin to the breaking changes of WP Bones 3');
      $this->info('make');
      $this->line(' make:ajax               Create a new Ajax service provider class');
      $this->line(' make:api                Create a new API controller class');
      $this->line(' make:app                Create a new React/TS app in resources/assets/apps');
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
      $this->line(' (make:* never overwrite an existing file: pass --force to do it)');
      $this->info('stub');
      $this->line(' stub:publish            Copy the stubs into stubs/, where make:* reads them first');

      if ($this->kernel && $this->kernel->hasCommands()) {
        $this->info('Extensions');
        $this->kernel->displayHelp();
      }

      echo "\n\n";
    }

    /* Run subtask. Handle the php bones commands */
    protected function handle()
    {
      // install
      if ($this->isCommand('install')) {
        $this->install($this->getCommandParams());
      }
      // optimize
      elseif ($this->isCommand('optimize')) {
        exit($this->optimize());
      }
      // plugin
      elseif ($this->isCommand('plugin')) {
        $this->plugin($this->getCommandParams());
      }
      // rename
      elseif ($this->isCommand('rename')) {
        $this->rename($this->getCommandParams());
      }
      // require
      elseif ($this->isCommand('require')) {
        $this->requirePackage($this->getCommandParams(0));
      }
      // update
      elseif ($this->isCommand('update')) {
        $this->update();
      }
      // version
      elseif ($this->isCommand('version')) {
        $this->version($this->getCommandParams());
      }
      // -- migrate ------------------------------------------
      //
      // migrate:create {table_name}
      elseif ($this->isCommand('migrate:create')) {
        $this->createMigrate($this->getCommandArgument());
      }
      // migrate:to-v2
      elseif ($this->isCommand('migrate:to-v2')) {
        $this->migrateToV2();
      }
      // migrate:to-v3
      elseif ($this->isCommand('migrate:to-v3')) {
        $this->migrateToV3();
      }
      // -- make ---------------------------------------------
      //
      // make:ajax {className}
      elseif ($this->isCommand('make:ajax')) {
        $this->createAjax($this->getCommandArgument());
      }
      // make:api {className}
      elseif ($this->isCommand('make:api')) {
        $this->createAPIController($this->getCommandArgument());
      }
      // make:app {appName}
      elseif ($this->isCommand('make:app')) {
        $this->createApp($this->getCommandArgument());
      }
      // make:console {command_name}
      elseif ($this->isCommand('make:console')) {
        $this->createCommand($this->getCommandArgument());
      }
      // make:controller {controller_name}
      elseif ($this->isCommand('make:controller')) {
        $this->createController($this->getCommandArgument());
      }
      // make:cpt {className}
      elseif ($this->isCommand('make:cpt')) {
        $this->createCustomPostType($this->getCommandArgument());
      }
      // make:ctt {className}
      elseif ($this->isCommand('make:ctt')) {
        $this->createCustomTaxonomyType($this->getCommandArgument());
      }
      // make:eloquent-model {className}
      elseif ($this->isCommand('make:eloquent-model')) {
        $this->createEloquentModel($this->getCommandArgument());
      }
      // make:model {className}
      elseif ($this->isCommand('make:model')) {
        $this->createModel($this->getCommandArgument());
      }
      // make:schedule {className}
      elseif ($this->isCommand('make:schedule')) {
        $this->createSchedule($this->getCommandArgument());
      }
      // make:shortcode {className}
      elseif ($this->isCommand('make:shortcode')) {
        $this->createShortcode($this->getCommandArgument());
      }
      // make:provider {className}
      elseif ($this->isCommand('make:provider')) {
        $this->createProvider($this->getCommandArgument());
      }
      // make:widget {className}
      elseif ($this->isCommand('make:widget')) {
        $this->createWidget($this->getCommandArgument());
      }
      // -- stub ---------------------------------------------
      //
      // stub:publish {stub ...} [--force]
      elseif ($this->isCommand('stub:publish')) {
        $this->publishStubs();
      }
      // -- kernel --------------------------------------------------
      //
      // check any registered kernel commands.
      else {
        $extended = false;

        if ($this->kernel) {
          $extended = $this->kernel->handle($this->arguments());
        }

        if (!$extended) {
          $this->error("Unknown command '{$this->arguments(0)}'. Use --help for the list of commands.");
          exit(1);
        }
      }
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

    /**
     * Update the plugin name and namespace
     *
     * Only files whose content changes are written, and files holding a NUL byte are left alone:
     * up to 2.0.12 every file was rewritten, and a compiled .mo catalogue came out corrupted,
     * because replacing "wp-kirk" with a longer id moves every string after it while the offset
     * table stays where it was.
     *
     * @param string $search_plugin_name The previous plugin name
     * @param string $search_namespace The previous namespace
     * @param string $plugin_name The new plugin name
     * @param string $namespace The new namespace
     * @param bool   $vendorOnly Only vendor/, what Composer has just installed (`rename --update`). Since 2.1.0.
     */
    protected function setPluginNameAndNamespace(
      $search_plugin_name,
      $search_namespace,
      $plugin_name,
      $namespace,
      bool $vendorOnly = false
    ) {
      $mainPluginFile = $this->getMainPluginFile($plugin_name);
      $currentMainPluginFile = $this->getMainPluginFile($search_plugin_name);

      // `rename --update` renames what Composer installed, never the plugin's own files. Up to 2.0.12
      // it went through the whole rename, and in a renamed plugin wp-kirk.php never exists, so an
      // index.php in the root was taken for a pre-1.5 main file and moved over the real one.
      if (!$vendorOnly) {
        // check if "index.php" exists
        if (file_exists('index.php') && !file_exists($currentMainPluginFile)) {
          $this->info("The file '{$currentMainPluginFile}' doesn't exists. Maybe you are updating from a < 1.5 version.");
          $currentMainPluginFile = 'index.php';
        }

        @rename($currentMainPluginFile, $mainPluginFile);
      }

      $files = $this->filesToRename($vendorOnly ? ['vendor'] : null);

      if (!$vendorOnly) {
        $files = array_merge($files, [$mainPluginFile, 'composer.json', 'readme.txt']);
      }

      // change namespace
      $this->startProgress('Processing files');
      $changed = 0;
      $seen = [];
      foreach ($files as $file) {
        $real = realpath($file);

        // A file reached twice (a symlink, a root file listed again) must not be replaced twice.
        if ($real === false || isset($seen[$real]) || !is_file($file)) {
          continue;
        }

        $seen[$real] = true;
        $original = file_get_contents($file);

        if ($original === false || strpos($original, "\0") !== false) {
          continue;
        }

        $content = $original;

        // change namespace
        $content = str_replace($search_namespace, $namespace, $content);

        // change slug
        $content = str_replace($this->getPluginSlug($search_plugin_name), $this->getPluginSlug($plugin_name), $content);

        // change vars
        $content = str_replace($this->getPluginVars($search_plugin_name), $this->getPluginVars($plugin_name), $content);

        // change id
        $content = str_replace($this->getPluginId($search_plugin_name), $this->getPluginId($plugin_name), $content);

        // change plugin name just in main plugin file and readme.txt
        if ($file === $mainPluginFile || $file === 'readme.txt') {
          $content = str_replace($search_plugin_name, $plugin_name, $content);
        }

        if ($content !== $original) {
          file_put_contents($file, $content);
          $changed++;
        }
      }
      $this->endProgress();
      $this->line(" {$changed} of " . count($seen) . ' files changed');

      if (!$vendorOnly) {
        // Saved before the header is read: getDomainPath() finds the main file through it, and the
        // old main file has just been renamed. Up to 2.0.12 the language files were renamed only by
        // the `rename --update` that Composer runs afterwards, and never on `rename --reset`.
        file_put_contents('namespace', "{$plugin_name},{$namespace}");

        // WordPress documents "Domain Path: /languages": with the slash it was looked for at the disk root.
        $folder = ltrim((string) $this->getDomainPath(), '/\\');

        if (!empty($folder) && is_dir($folder)) {
          foreach (glob($folder . '/*') as $file) {
            $newFile = str_replace($this->getPluginId($search_plugin_name), $this->getPluginId($plugin_name), $file);
            rename($file, $newFile);
          }
        }

        foreach (glob('resources/assets/js/*') as $file) {
          $newFile = str_replace($this->getPluginId($search_plugin_name), $this->getPluginId($plugin_name), $file);
          rename($file, $newFile);
        }

        foreach (glob('resources/assets/css/*') as $file) {
          $newFile = str_replace($this->getPluginId($search_plugin_name), $this->getPluginId($plugin_name), $file);
          rename($file, $newFile);
        }
      }

      // Change also the WP Bones plugin class
      $file = 'vendor/wpbones/wpbones/src/Foundation/Plugin.php';
      if (is_file($file)) {
        $original = file_get_contents($file);
        $content = str_replace($currentMainPluginFile, $mainPluginFile, $original);
        if ($content !== $original) {
          file_put_contents($file, $content);
        }
      }

      $this->processCompleted('Rename process completed!');
    }

    /**
     * Create a directory if it does not exist.
     *
     * @param string $path The path of the directory to create.
     *
     * @since 1.9.2
     *
     * @return bool True if the directory was created or already exists, false otherwise.
     */
    protected function mkdirIfNotExists(string $path): bool
    {
      if (!file_exists($path)) {
        mkdir($path, 0755, true);
      }
      return true;
    }

    /**
     * The files a rename reads: every file under the plugin's folders (or under $folders), without
     * node_modules, dot entries and the bones source. Files in the plugin root are not included,
     * as with the glob this replaces: the rename adds the three it changes by name.
     *
     * Up to 2.0.12 this was recursiveScan(), which declared the function _rglob() inside a method, so
     * a second call in one process was a "Cannot redeclare" fatal, and which followed a symlinked
     * folder back into itself until the path was too long.
     *
     * @since 2.1.0
     * @param string[]|null $folders Folders relative to the plugin root; null for all of them.
     *
     * @return string[] Paths relative to the plugin root.
     */
    protected function filesToRename(?array $folders = null): array
    {
      $pending = [];

      foreach ($folders ?? (glob('*', GLOB_ONLYDIR) ?: []) as $folder) {
        if ($folder !== 'node_modules' && is_dir($folder)) {
          $pending[] = $folder;
        }
      }

      $files = [];
      $visited = [];

      while ($pending) {
        $folder = array_pop($pending);
        $real = realpath($folder);

        if ($real === false || isset($visited[$real])) {
          continue;
        }

        $visited[$real] = true;

        foreach (scandir($folder) ?: [] as $entry) {
          // glob('*') never matched dot entries: .git, .github, .DS_Store stay out.
          if ($entry[0] === '.') {
            continue;
          }

          $path = "{$folder}/{$entry}";

          if (is_dir($path)) {
            if ($entry !== 'node_modules') {
              $pending[] = $path;
            }

            continue;
          }

          if ($path !== 'vendor/wpbones/wpbones/src/Console/bin/bones') {
            $files[] = $path;
          }
        }
      }

      sort($files);

      return $files;
    }

    /* Update the plugin name and namespace after a install new package */
    protected function updatePluginNameAndNamespace()
    {
      // use the current plugin name and namespace from the namespace file
      $plugin_name = $this->getPluginName();
      $namespace = $this->getNamespace();
      [$search_plugin_name, $search_namespace] = $this->getDefaultPluginNameAndNamespace();
      $this->setPluginNameAndNamespace($search_plugin_name, $search_namespace, $plugin_name, $namespace, true);
    }

    /**
     * Get the plugin name and namespace from args or ask them from the console
     *
     * @param array $args The arguments from the console. The first argument is the plugin name and the second is the namespace
     *
     */
    protected function askPluginNameAndNamespace(array $args): array
    {
      // Get the current plugin name and namespace
      $search_plugin_name = $this->getPluginName();
      $search_namespace = $this->getNamespace();

      if ($search_plugin_name === 'WP Kirk' && $search_namespace === 'WPKirk') {
        $this->line("→ You are renaming your plugin for the first time.\n");
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
        $plugin_name = $this->ask('Plugin name', $plugin_name);
        $namespace = $this->ask('Namespace', $namespace);

        // With nothing left to read the loop would ask forever.
        if (empty($plugin_name) && $this->inputEnded) {
          $this->error('No plugin name given: nothing was renamed.');
          exit(1);
        }

        // both plugin name and namespace don't have to contains 'WP Kirk' or 'WPKirk'
        if (strpos($plugin_name, 'WP Kirk') !== false || strpos($plugin_name, 'WPKirk') !== false) {
          $this->warning('Plugin name cannot contain "WP Kirk" or "WPKirk"');
          $plugin_name = '';
          continue;
        }

        if (strpos($namespace, 'WP Kirk') !== false || strpos($namespace, 'WPKirk') !== false) {
          $this->warning('Namespace cannot contain "WP Kirk" or "WPKirk"');
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
      $fields = array_unique(
        array_merge(
          ...array_map(function ($field) {
            return is_array($field) ? $field : [$field];
          }, $fields),
        ),
      );

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

    /* Execute composer install */
    protected function install()
    {
      if ($this->isHelp()) {
        $this->line("Will run the composer install\n");
        $this->info('Usage:');
        $this->line(' php bones install');
        exit();
      }

      $this->startCommand('Install');

      // check if the "node_modules" folder exists
      if (!is_dir('node_modules')) {
        $this->warning('Node modules not found');
        $this->installPackages();
      }

      exit($this->runShell('composer install'));
    }

    /**
     * Install node modules
     *
     * @since 1.9.0
     */
    protected function installPackages()
    {
      // ask to install node modules
      $yesno = $this->ask('Do you want to install node modules (y/n)', 'n');
      if (strtolower($yesno) === 'y') {
        $packageManager = $this->askPackageManager();
        shell_exec("{$packageManager} install");
        $this->processCompleted("Node modules created and packages installed successfully\n");
      }
    }

    /* Alias composer dump-autoload */
    protected function optimize()
    {
      $this->startCommand('Optimize');

      $status = $this->runShell('composer dump-autoload -o');

      if ($status !== 0) {
        $this->warning("composer dump-autoload -o exited with status {$status}: run it by hand to see why.");

        return $status;
      }

      $this->processCompleted('Optimize process completed!');

      return 0;
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

      $this->startCommand('Rename');

      $arg_option_plugin_name = $args[0] ?? null;

      switch ($arg_option_plugin_name) {
        case '--reset':
          $this->resetPluginNameAndNamespace();
          break;
        case '--update':
          $this->updatePluginNameAndNamespace();
          break;
        default:
          [
            $search_plugin_name,
            $search_namespace,
            $plugin_name,
            $namespace,
            $mainPluginFile,
          ] = $this->askPluginNameAndNamespace($args);
          $this->line(
            "\n→ The new plugin filename, name and namespace will be '{$mainPluginFile}', '{$plugin_name}', '{$namespace}'",
          );
          $yesno = $this->ask('Continue (y/n)', 'n');
          if (strtolower($yesno) != 'y') {
            $this->error('Aborted: the plugin was not renamed.');
            exit(1);
          }
          $this->setPluginNameAndNamespace($search_plugin_name, $search_namespace, $plugin_name, $namespace);
          $this->optimize();
          break;
      }
    }

    /**
     * Update the framework: `composer update wpbones/wpbones --with-dependencies`.
     *
     * Up to 2.0.12 vendor/wpbones/wpbones was deleted first and a full `composer update` ran after:
     * when Composer failed (no network, a conflict) the plugin was left with no framework at all.
     * Composer replaces the package by itself, renamed files included: measured with Composer 2.10
     * on a dist install and on a git clone carrying 82 files changed by the rename.
     *
     * @since 2.1.0 Only the framework and its dependencies, and nothing is deleted first.
     */
    protected function update()
    {
      if ($this->isHelp()) {
        $this->line("Will run composer update wpbones/wpbones --with-dependencies: the framework and the packages it needs.");
        $this->line("To update every package, run composer update.\n");
        $this->info('Usage:');
        $this->line(' php bones update');
        exit();
      }

      $status = $this->runShell('composer update wpbones/wpbones --with-dependencies');

      if ($status !== 0) {
        $this->error("composer update exited with status {$status}.");
      }

      exit($status);
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
     * Create a deployment version of the plugin
     *
     * @param $argv
     *
     * @since 1.9.2 - If WordPress is not loaded, the deploy.php script will not be executed.
     */
    protected function deploy($argv)
    {
      // Check if there is '--wp' in the arguments array.
      $is_wp_org = in_array('--wp', $argv, true);

      // Check if there is '--create-zip' in the arguments array.
      $is_create_zip = in_array('--create-zip', $argv, true);

      // Check if there is '--no-build' in the arguments array.
      $no_build = in_array('--no-build', $argv, true);

      // Check if there is '--keep-ignored' in the arguments array.
      $keep_ignored = in_array('--keep-ignored', $argv, true);

      // Check if there is '--keep-dev' in the arguments array.
      $keep_dev = in_array('--keep-dev', $argv, true);

      // Check if there is '--force' in the arguments array.
      $force = in_array('--force', $argv, true);

      // Check if there is '--pkgm=<package-manager>' in the arguments array,
      // and get the <package-manager> name.
      $package_name = $this->getOptionValue($argv, '--pkgm=');

      // Filter the array to remove the '--wp', '--create-zip', '--no-build',
      // and '--pkgm=<package-manager>' arguments.
      $filtered_args = $this->removeValues($argv, [
        '--wp',
        '--create-zip',
        '--no-build',
        '--keep-ignored',
        '--keep-dev',
        '--force',
        '--pkgm=' . $package_name,
      ]);

      // Get the path from the filtered arguments array.
      $path = $filtered_args[0] ?? '';
      $path = rtrim($path, '/');

      if (empty($path)) {
        $path = $this->ask('Enter the complete path of deploy');
      } elseif ('--help' === $path) {
        $this->line("\nUsage:");
        $this->info("  deploy <path> <options>\n");
        $this->line('Arguments:');
        $this->info("  path\t\tThe complete path of deploy.\n");
        $this->line('Options:');
        $this->info("  --wp\t\t\t\tYou are going to release this plugin in the WordPress.org public repository.");
        $this->info("  --create-zip\t\t\tCreates an installable plugin zip file instead of deploying in a directory.");
        $this->info(
          "  --pkgm=<package-manager>\tForces the deployment to use the specified package manager, Eg. npm, yarn, ...",
        );
        $this->info("  --no-build\t\t\tForces the deployment to skip the build process.");
        $this->info(
          "  --keep-ignored\t\tPackages files that git ignores as well (they are left out by default).",
        );
        $this->info(
          "  --keep-dev\t\t\tPackages Composer's require-dev packages as well (they are removed by default).",
        );
        $this->info(
          "  --force\t\t\tReplaces a destination folder that is neither empty nor a previous deploy of this plugin.",
        );
        exit(0);
      }

      $this->startCommand('Deploy');

      if (empty($path)) {
        $this->error('The path is empty!');
        exit(1);
      }

      $path = $this->pathFromCommandLine($path);

      // Checked before anything is built: the folder the deploy is about to delete and refill.
      $this->assertSafeDeployDestination($is_create_zip ? $path . '-tmp' : $path, $force);

      // Alternative method to customize the deployment. It is included BEFORE the first hook fires:
      // until 2.0.6 wpbones_console_deploy_start ran first, so a deploy.php callback for it was
      // registered after the action and never ran.
      if ($this->wpLoaded) {
        @include 'deploy.php';
      } else {
        $this->warning('This plugin looks like is not inside a WordPress. The "deploy.php" won\'t be use');
      }

      $this->do_action('wpbones_console_deploy_start', $this, $path);

      /**
       * Filter the list of files and folders that won't be skipped during the deployment.
       *
       * @since 1.9.0
       * @param array $array The files and folders are relative to the root of plugin.
       */
      $dontSkipWhenDeploy = $this->apply_filters('wpbones_console_deploy_dont_skip_files_folders', [
        '/gulpfile.js',
        '/package.json',
        '/package-lock.json',
        '/yarn.lock',
        '/tsconfig.json',
        '/pnpm-lock.yaml',
        '/resources/assets',
        '/composer.json',
        '/composer.lock',
      ]);

      if ($is_wp_org) {
        $this->info("\n🚦 You are going to release this plugin in the WordPress.org public repository");
        $this->line("\n→ In this case some files won't be skipped during the deployment.");
        $this->line("→ The files that won't be skipped are:\n\n" . implode("\n", $dontSkipWhenDeploy) . "\n\n");
      }

      /**
       * Filter to enable or disable the build assets during the deployment.
       *
       * @since 1.9.0
       * @param bool $buildAssets True to build assets, false to skip the build.
       */
      $buildAssets = $this->apply_filters('wpbones_console_deploy_build_assets', true);

      if ($buildAssets && !$no_build) {
        $this->do_action('wpbones_console_deploy_before_build_assets', $this, $path);
        $this->buildAssets($path, $package_name);
      }

      // When creating a zip, a temporary folder must be created with the deployed version first.
      // Append "-tmp" to the path. This folder will be deleted at the end of the deployment.
      if ($is_create_zip) {
        $originalPath = $path;
        $path .= '-tmp';
      }

      // Check if the destination folder exists.
      if (is_dir($path)) {
        $this->startProgress("Delete destination folder 📁 {$path}");
        $this->deleteDirectory($path);
        $this->endProgress();
      }

      /**
       * Filter the default list of files and folders to skip during the deployment.
       *
       * @since 1.9.0
       * @param array $array The files and folders are relative to the root of plugin.
       */
      $this->skipWhenDeploy = $this->apply_filters('wpbones_console_deploy_default_skip_files_folders', [
        '/.git',
        '/.github',
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
        '/Dockerfile',
        '/webpack.mix.js',
        '/webpack.config.js',
        '/phpcs.xml.dist',
        '/mix-manifest.json',
        '/release.sh',
        ...$this->pluginStubsToSkip(),
      ]);

      // The new strict rules require that the composer must also be released to publish the plugin in the WordPress.org repository.
      if (!$is_wp_org) {
        $this->skipWhenDeploy = array_merge($this->skipWhenDeploy, $dontSkipWhenDeploy);
      }

      /**
       * Filter the list of files and folders to skip during the deployment.
       *
       * @param array $array The files and folders are relative to the root of plugin.
       */
      $this->skipWhenDeploy = $this->apply_filters('wpbones_console_deploy_skip_folders', $this->skipWhenDeploy);

      if (!$keep_ignored) {
        $ignored = $this->gitIgnoredEntries();

        if (!empty($ignored)) {
          $this->skipWhenDeploy = array_merge($this->skipWhenDeploy, $ignored);
          $this->info(
            '🙈 Leaving out ' .
              count($ignored) .
              ' path(s) git ignores: ' .
              implode(', ', array_map(fn($entry) => ltrim($entry, '/'), $ignored)),
          );
          $this->line('   Pass --keep-ignored to package them anyway.');
        }
      }

      $this->rootDeploy = __DIR__;

      $this->startProgress("Copying to 📁 {$path}");
      $this->xcopy(__DIR__, $path);
      $this->endProgress();

      if (!$keep_dev) {
        $this->pruneDevPackages($path);
      }

      /**
       * Fires when the console deploy is completed.
       *
       * @param mixed $bones Bones command instance.
       * @param string $path The deployed path.
       */
      $this->do_action('wpbones_console_deploy_completed', $this, $path);

      // Create a zip if "--create-zip" is passed as an argument.
      if ($is_create_zip) {
        // Return an error if the zip extension is disabled.
        if (!extension_loaded('zip')) {
          // Delete the temporary folder once the zip can't be created.
          $this->deleteDirectory($path);
          $this->error("Can't create zip! PHP zip extension is disabled.");
          exit(1);
        }

        // If the path ends with a trailing slash, remove it.
        if (substr($originalPath, -1) === DIRECTORY_SEPARATOR) {
          $originalPath = substr($originalPath, 0, -1);
        }

        // If the path doesn't end with .zip, add it.
        if (substr($originalPath, -4) !== '.zip') {
          $originalPath .= '.zip';
        }

        // Create zip file if it doesn't exist. Needed for realpath().
        fopen($originalPath, 'wa+');

        // Create the zip by iterating over the folders and files and adding them to the zip.
        $this->startProgress("Creating zip file 📁 {$originalPath}");
        $zip = new \ZipArchive();
        $zip->open(realpath($originalPath), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $this->addDirToZip($zip, realpath($path));
        $zip->close();
        $this->endProgress();

        // Delete the temporary folder after the zip is created.
        $this->deleteDirectory($path);
        // Update the path to the zip file for info message below.
        $path = $originalPath;
      }

      $this->success('Deploy completed!');
      $this->info("\n🚀 You can now deploy the plugin from the path: {$path}\n");
    }

    /**
     * Recursively adds all files and directories from $folderPath to $zip with the correct relative paths.
     *
     * @since 1.9.4
     * @param \ZipArchive $zip
     * @param string      $folderPath
     *
     * @return void
     */
    protected function addDirToZip($zip, $folderPath)
    {
      $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST,
      );

      foreach ($iterator as $item) {
        $realPath = $item->getRealPath();
        $relativePath = substr($realPath, strlen($folderPath) + 1);

        if ($item->isDir()) {
          // Create empty directory.
          $zip->addEmptyDir($relativePath);
        } else {
          // Add file to the proper path in the zip.
          $zip->addFile($realPath, $relativePath);
        }
      }
    }

    /**
     * Build assets
     *
     * @since 1.9.0
     * @param string $path The path of the deploy
     *
     * @since 1.9.2
     * @param string $pkgm Optional. The package manager to use for building assets
     */
    protected function buildAssets($path, $pkgm = '')
    {
      $packageManager = empty($pkgm) ? $this->askPackageManager() : $pkgm;

      if ($packageManager) {
        // ask to build assets
        $answer = empty($pkgm)
          ? $this->ask("Do you want to run '$packageManager run build' to build assets? (y/n)", 'y')
          : 'y';

        if (strtolower($answer) === 'y') {
          $this->startProgress("Build for production by using '{$packageManager} run build'");
          $this->runBuild($packageManager);
          $this->processCompleted("Build completed\n");
          $this->do_action('wpbones_console_deploy_after_build_assets', $this, $path);
        } else {
          $answer = $this->ask('Enter the package manager to build assets (press RETURN to skip the build)', '');
          if (empty($answer)) {
            $this->info('⏭︎ Skip build assets');
          } else {
            $this->startProgress("Build for production by using '{$answer} run build'");
            $this->runBuild($answer);
            $this->endProgress();
            $this->do_action('wpbones_console_deploy_after_build_assets', $this, $path);
          }
        }
      } else {
        $this->warning('No package manager found. The build assets will be skipped');
      }
    }

    /**
     * Run the production build and abort the deploy if it fails.
     *
     * This used to be a bare shell_exec(), which returns stdout and drops the exit
     * status on the floor. A build that failed still printed "Build completed", the
     * deploy went on to package whatever happened to be sitting in public/, and the
     * command exited 0 — so a broken release looked exactly like a good one. Aborting
     * is the right answer rather than warning: everything after this point copies the
     * build output into the release package.
     *
     * @since 2.0.6
     * @param string $packageManager The package manager used to run the build.
     *
     * @return void
     */
    protected function runBuild($packageManager)
    {
      $output = [];
      $status = 0;

      exec("{$packageManager} run build 2>&1", $output, $status);

      if ($status === 0) {
        return;
      }

      $this->error("\nBuild failed: '{$packageManager} run build' exited with status {$status}.");

      foreach (array_slice($output, -20) as $line) {
        $this->line('   ' . $line);
      }

      $this->error('Deploy aborted: the package would have shipped whatever was already in public/.');

      exit(1);
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
        if ($entry === '.' || $entry === '..' || strpos($entry, '.') === 0 || $this->skip("{$source}/{$entry}")) {
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
    /**
     * Paths inside the plugin that git ignores, as deploy-relative entries.
     *
     * The deploy copies the plugin directory filtered only by a hardcoded list, so
     * anything left lying around ships: a local POST.md went out to WordPress.org twice
     * before anyone noticed, invisible to `git status` and absent from the deploy log.
     *
     * Three directories are never consulted, because the repository's ignore rules do
     * not describe them. `vendor/` is gitignored in all fourteen boilerplates and partly
     * gitignored in the released plugins, so honouring .gitignore literally would ship a
     * plugin with no framework in it. `public/` holds what the build has just produced,
     * and `storage/` is the runtime directory.
     *
     * Returns an empty list when the plugin is not itself a git repository, or when git
     * is unavailable — nothing to consult, so nothing is filtered.
     *
     * @since 2.0.6
     *
     * @return array
     */
    protected function gitIgnoredEntries(): array
    {
      $root = escapeshellarg(__DIR__);
      $topLevel = [];
      $status = 0;

      exec("git -C {$root} rev-parse --show-toplevel 2>/dev/null", $topLevel, $status);

      // Only the plugin's own repository. A plugin sitting inside someone else's
      // checkout must not inherit that repository's ignore rules.
      if ($status !== 0 || realpath(trim($topLevel[0] ?? '')) !== realpath(__DIR__)) {
        return [];
      }

      $lines = [];
      $status = 0;

      exec(
        "git -C {$root} ls-files --others --ignored --exclude-standard --directory 2>/dev/null",
        $lines,
        $status,
      );

      if ($status !== 0) {
        return [];
      }

      $keep = ['vendor', 'public', 'storage'];
      $entries = [];

      foreach ($lines as $line) {
        $path = rtrim(trim($line), '/');

        if ($path === '' || in_array(explode('/', $path)[0], $keep, true)) {
          continue;
        }

        $entries[] = '/' . $path;
      }

      return array_values(array_unique($entries));
    }

    /**
     * Remove Composer's require-dev packages from the deployed copy.
     *
     * The deploy copies vendor/ as it is on disk, and a working copy normally has the dev tree
     * installed (PHPUnit, Brain Monkey, ...): 2.0.6 shipped all of it unless the plugin ran
     * `composer install --no-dev` itself. The source directory is never touched: Composer runs in
     * the copy, with --no-scripts (post-autoload-dump would copy bones and rename namespaces again)
     * and --no-plugins, against the plugin's own composer.lock. Nothing happens when installed.json
     * lists no dev package, i.e. when the dev tree is not installed or there is none.
     */
    protected function pruneDevPackages(string $path): void
    {
      $installed = __DIR__ . '/vendor/composer/installed.json';

      if (!is_file($installed)) {
        return;
      }

      $data = json_decode((string) file_get_contents($installed), true);
      $dev = is_array($data) ? $data['dev-package-names'] ?? [] : [];

      if (empty($dev)) {
        return;
      }

      $list = implode(', ', $dev);

      if (!is_file(__DIR__ . '/composer.lock')) {
        $this->error(
          "The package would ship Composer's dev packages ({$list}) and there is no composer.lock to remove them against. Run `composer install --no-dev` before deploying, or pass --keep-dev.",
        );
        exit(1);
      }

      $composer = $this->composerCommand();

      if ($composer === null) {
        $this->error(
          "The package would ship Composer's dev packages ({$list}) and Composer was not found to remove them. Install Composer, or pass --keep-dev.",
        );
        exit(1);
      }

      // The copy needs composer.json and composer.lock to be pruned; they stay only if they were
      // deployed anyway (--wp).
      $added = [];
      foreach (['composer.json', 'composer.lock'] as $file) {
        if (!is_file("{$path}/{$file}")) {
          copy(__DIR__ . "/{$file}", "{$path}/{$file}");
          $added[] = "{$path}/{$file}";
        }
      }

      $this->startProgress('Removing ' . count($dev) . " Composer dev package(s) from the package: {$list}");
      exec(
        $composer .
          ' install --no-dev --no-scripts --no-plugins --no-interaction --no-progress --optimize-autoloader --working-dir=' .
          escapeshellarg($path) .
          ' 2>&1',
        $output,
        $status,
      );
      $this->endProgress();

      foreach ($added as $file) {
        unlink($file);
      }

      if ($status !== 0) {
        $this->error("`composer install --no-dev` failed in the package, which still holds the dev packages:\n" . implode("\n", $output));
        exit(1);
      }
    }

    /**
     * The command that runs Composer: a composer.phar in the plugin root, else `composer` on PATH.
     */
    protected function composerCommand(): ?string
    {
      if (is_file(__DIR__ . '/composer.phar')) {
        return escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/composer.phar');
      }

      // `command -v` is POSIX. cmd.exe has `where`, which lists every match — the extensionless
      // shell script of the Composer installer before composer.bat — so on Windows it only proves
      // Composer is on PATH, and the bare name lets cmd.exe pick composer.bat through PATHEXT.
      if (PHP_OS_FAMILY === 'Windows') {
        return trim((string) shell_exec('where composer 2>NUL')) !== '' ? 'composer' : null;
      }

      $found = trim((string) shell_exec('command -v composer 2>/dev/null'));

      return $found !== '' ? escapeshellarg($found) : null;
    }

    protected function skip(string $value): bool
    {
      $single = str_replace($this->rootDeploy, '', $value);

      return in_array($single, $this->skipWhenDeploy);
    }

    /**
     * Start a Tinker emulation: read a line, run it, print what it returns, repeat.
     *
     * Up to 2.0.9 each prompt was a recursive call from `finally`, so input that ended without
     * `exit` recursed until the process died; only Exception was caught, so an Error (a typo, an
     * undefined function) printed nothing at all; and the catch block ran eval() on the
     * exception's message instead of printing it.
     *
     * @since 2.0.10 A loop that ends with `exit` or with its input, and reports every Throwable.
     */
    protected function tinker()
    {
      if ($this->isHelp()) {
        $this->line("\nUsage:");
        $this->info("  tinker\n");
        exit();
      }

      if (!$this->wpLoaded) {
        $this->warning("Note: WordPress is not loaded! This means you can't use the WP functions.\n");
      }

      while (true) {
        $input = readline(WPBONES_COLOR_BOLD_GREEN . '〉' . WPBONES_COLOR_LIGHT_GREEN);
        echo WPBONES_COLOR_RESET;

        // Ctrl-D, or the end of piped input.
        if ($input === false) {
          echo "\n";

          return;
        }

        $eval = trim($input);

        if ($eval === 'exit') {
          return;
        }

        if ($eval === '') {
          continue;
        }

        if (function_exists('readline_add_history')) {
          readline_add_history($eval);
        }

        if (substr($eval, -1) !== ';') {
          $eval .= ';';
        }

        try {
          $result = eval($eval);

          if ($result !== null) {
            echo is_scalar($result) ? $result : print_r($result, true);
          }
        } catch (\Throwable $e) {
          $this->error(get_class($e) . ': ' . $e->getMessage());
        }

        echo "\n";
      }
    }

    /**
     * Install a new composer package
     *
     * @param string $package The composer package to install
     */
    protected function requirePackage(?string $package = '')
    {
      if ($this->isHelp($package)) {
        $this->info('Use php bones require <PackageName>');
        exit();
      }

      $this->startCommand('Require');

      if (empty($package)) {
        $package = $this->ask('Enter the composer package to install');
      }

      if (empty($package)) {
        $this->error('The package is empty!');
        exit(1);
      }

      $status = $this->runShell('composer require ' . escapeshellarg($package));

      if ($status !== 0) {
        $this->error("composer require {$package} exited with status {$status}: nothing was renamed.");
        exit($status);
      }

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
      $this->startCommand('Version');

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
          // The version is in the format of: Stable tag: 1.0.0
          $stable_tag_version_from_readme_txt = $matches[0];

          // The version is in the format of: 1.0.0 or 1.0.0-beta.1 or 1.0.0-alpha.1 or 1.0.0-rc.1
          $version_number_from_readme_txt = $matches[1];

          $this->info("\n→ readme.txt > $version_number_from_readme_txt ($stable_tag_version_from_readme_txt)");
          break;
        }
      }

      // parse the $mainPluginFilename version
      $lines = explode("\n", $index_php_content);
      foreach ($lines as $line) {
        // get the plugin version for WordPress comments
        if (preg_match('/^[ \t\/*#@]*Version:\s*(.*)$/i', $line, $matches)) {
          // The version is in the format of: * Version: 1.0.0
          $version_string_from_index_php = $matches[0];

          // The version is in the format of: 1.0.0
          $version_number_from_index_php = $matches[1];

          $this->info("→ $mainPluginFilename  > {$version_number_from_index_php} ($version_string_from_index_php)\n");
          break;
        }
      }

      if ($version_number_from_index_php != $version_number_from_readme_txt) {
        $this->error("\nWARNING:\n\nThe version in readme.txt and $mainPluginFilename are different.");
      }

      if (!isset($argv[0]) || empty($argv[0])) {
        $version = $this->ask('Enter new version of your plugin');
      } elseif ($this->isHelp()) {
        $this->line("\nUsage:");
        $this->info("  version [plugin version]\n");
        $this->line('Arguments:');
        $this->info(
          "  [plugin version]\t\tThe version of plugin. Examples: '2.0',  'v1.2',  '1.2.0-rc.40', 'v1-beta.4'",
        );
        $this->info("  [--major]\t\t\tIncrement the <major>.y.z of plugin.");
        $this->info("  [--minor]\t\t\tIncrement the x.<minor>.z of plugin.");
        $this->info("  [--patch]\t\t\tIncrement the x.y.<patch> of plugin.");
        $this->info("  [--pre-patch] <prefix>\tIncrement the x.y.<patch>-<prefix>.<i> of plugin.");
        $this->info("  [--pre-minor] <prefix>\tIncrement the x.<minor>.z-<prefix>.<i> of plugin.");
        $this->info("  [--pre-major] <prefix>\tIncrement the <major>.y.z-<prefix>.<i> of plugin.\n");
        exit(0);
      } elseif (isset($argv[0]) && '--patch' === $argv[0]) {
        $version = semver($version_number_from_index_php)->incrementPatch();
      } elseif (isset($argv[0]) && '--minor' === $argv[0]) {
        $version = semver($version_number_from_index_php)->incrementMinor();
      } elseif (isset($argv[0]) && '--major' === $argv[0]) {
        $version = semver($version_number_from_index_php)->incrementMajor();
      } elseif (isset($argv[0]) && in_array($argv[0], ['--pre-patch', '--pre-major', '--pre-minor'])) {
        $prefix = $argv[1] ?? 'rc';

        $methods = [
          '--pre-patch' => 'incrementPatch',
          '--pre-major' => 'incrementMajor',
          '--pre-minor' => 'incrementMinor',
        ];
        // if $version_number_from_index_php is not a pre-release version
        if (strpos($version_number_from_index_php, $prefix) === false) {
          $prerelease = semver($version_number_from_index_php)->{$methods[$argv[0]]}();
          $version = semver($prerelease)->setPreRelease($prefix)->incrementPreRelease();
        } else {
          $version = semver($version_number_from_index_php)->incrementPreRelease();
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
        $this->error('Error! The version is not valid.');
        exit(1);
      }

      $yesno = $this->ask("The new version of your plugin will be {$version}, is it ok? (y/n)", 'n');

      if (strtolower($yesno) != 'y') {
        // Also what a script gets when nothing answers: say so, and fail, so it cannot pass for a bump.
        $this->error("Aborted: the version was not changed. In a script, answer the question: echo y | php bones version {$version}");
        exit(1);
      }

      if ($version != $version_number_from_index_php || $version != $version_number_from_readme_txt) {
        // We're going to change the "Stable tag: x.y.z" with "Stable tag: $version"
        $new_stable_tag_version_for_readme_txt = str_replace(
          $version_number_from_readme_txt,
          $version,
          $stable_tag_version_from_readme_txt,
        );

        // We're going to change the whole "readme.txt" file
        $new_readme_txt_content = str_replace(
          $stable_tag_version_from_readme_txt,
          $new_stable_tag_version_for_readme_txt,
          $readme_txt_content,
        );

        file_put_contents('readme.txt', $new_readme_txt_content);

        // We're going to change the "* Version: x.y.z" with "* Version: $version"
        $new_version_string_for_index_php = str_replace(
          $version_number_from_index_php,
          $version,
          $version_string_from_index_php,
        );

        // We're going to change the whole main plugin file
        $new_index_php_content = str_replace(
          $version_string_from_index_php,
          $new_version_string_for_index_php,
          $index_php_content,
        );

        file_put_contents($mainPluginFilename, $new_index_php_content);

        // The asset pipeline keeps its own copy of the version. Without these the
        // command reported success while package.json silently drifted behind.
        $this->updateJsonVersion('package.json', (string) $version, 1);
        $this->updateJsonVersion('package-lock.json', (string) $version, 2);

        $this->processCompleted("Version updated to {$version}");

        return;
      }

      $this->warning("Version is already {$version}");
    }

    /**
     * Update the plugin's own "version" entries in a JSON file, leaving the rest alone.
     *
     * package.json has one; a package-lock.json has two, `version` at the top and
     * `packages[""].version`, and then one for every installed dependency. Everything
     * from the first "node_modules/" key on is the dependency tree and must not move,
     * so the replacement only ever looks at the text before it.
     *
     * A v1 lockfile (npm 6) has no "node_modules/" key and no `packages`: its tree
     * sits under `dependencies`, so only the `version` at the top is the plugin's
     * (#102).
     *
     * The file is edited as text rather than decoded and re-encoded: npm writes two
     * space indentation and PHP's JSON_PRETTY_PRINT writes four, so a round trip would
     * rewrite every line of the file to change one number.
     *
     * @since 2.0.6
     * @param string $filename The file to update, relative to the plugin root.
     * @param string $version  The new version.
     * @param int    $limit    How many entries to replace at most.
     *
     * @return void
     */
    protected function updateJsonVersion($filename, $version, $limit)
    {
      if (!file_exists($filename)) {
        return;
      }

      $content = file_get_contents($filename);

      if (preg_match('/"lockfileVersion"\s*:\s*1\b/', $content)) {
        $limit = 1;
      }

      $boundary = strpos($content, '"node_modules/');
      $head = $boundary === false ? $content : substr($content, 0, $boundary);
      $tail = $boundary === false ? '' : substr($content, $boundary);

      $count = 0;
      $head = preg_replace('/("version"\s*:\s*")[^"]*(")/', '${1}' . $version . '${2}', $head, $limit, $count);

      if ($count === 0) {
        $this->warning("→ {$filename} has no \"version\" entry, left as it is");

        return;
      }

      file_put_contents($filename, $head . $tail);

      $this->info("→ {$filename} > {$version} (" . $count . ($count === 1 ? ' entry)' : ' entries)'));
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
          $this->success($key . ': ');
          $this->info($header[$key]);
        } else {
          $this->warning($key . ': ', false);
          $this->color(" Not found \n", WPBONES_COLOR_RED);
        }
      }
    }

    /**
     * Run the migrations that have not run on this site, the same way the plugin does by itself
     * when its version changes. The way to run one added during development, before a version bump.
     *
     * @since 3.0.0
     */
    protected function migrate()
    {
      if ($this->isHelp()) {
        $this->info('Use php bones migrate');
        $this->line('Runs the migrations in database/migrations/ that have not run on this site, in file name order.');

        return;
      }

      $result = $this->migratingPlugin()->runMigrations();

      if ($result->locked) {
        $this->error('Another request is running the migrations right now: try again in a moment.');
        exit(1);
      }

      foreach ($result->ran as $name) {
        $this->line(" ✓ {$name}");
      }

      foreach ($result->outOfOrder as $name) {
        $this->warning("{$name} ran after migrations whose names sort later: check that it does not depend on them.");
      }

      if ($result->failed !== null) {
        $this->error("✗ {$result->failed}: {$result->error}");
        $this->line('The migrations after it did not run. Fix it and run php bones migrate again.');
        exit(1);
      }

      if ($result->ran === []) {
        // An active plugin runs them itself while WordPress loads, when its version changed.
        $this->info('Nothing to migrate. php bones migrate:status lists what ran.');

        return;
      }

      $this->success(sprintf('%d migration%s ran.', count($result->ran), count($result->ran) === 1 ? '' : 's'));
    }

    /**
     * List the migrations, and for each one whether it ran on this site.
     *
     * @since 3.0.0
     */
    protected function migrateStatus()
    {
      if ($this->isHelp()) {
        $this->info('Use php bones migrate:status');

        return;
      }

      $migrator = $this->migratingPlugin()->migrator();
      $status = $migrator->status();

      if ($status === []) {
        $this->info('No migrations in database/migrations/.');
      }

      foreach ($status as $name => $state) {
        if ($state['ran']) {
          $this->line(" ✓ {$name}  (batch {$state['batch']}, version {$state['version']})");
        } elseif ($state['outOfOrder']) {
          $this->warning(" · {$name}  (pending, and it sorts before a migration that already ran)");
        } else {
          $this->line(" · {$name}  (pending)");
        }
      }

      $failure = $migrator->failure();

      if ($failure !== null) {
        $this->error("The last run stopped at {$failure['migration']}: {$failure['message']}");
      }

      if (glob('database/seeders/*.php')) {
        $this->warning('database/seeders/ does not run since WP Bones 3.0: php bones migrate:to-v3 turns the seeders into migrations.');
      }
    }

    /**
     * The plugin, booted inside the WordPress the migrations will write to.
     *
     * @since 3.0.0
     */
    protected function migratingPlugin()
    {
      if (!$this->wpLoaded) {
        $this->error('No WordPress found above this plugin: the migrations need its database.');
        exit(1);
      }

      $class = $this->getNamespace() . '\\WPBones\\Foundation\\Plugin';
      $plugin = class_exists($class) ? $class::getInstance() : null;

      if (!$plugin || !method_exists($plugin, 'migrator')) {
        $this->error('This plugin runs a WP Bones older than 3.0, which has no migrator: run php bones update.');
        exit(1);
      }

      return $plugin;
    }

    /**
     * Create a migrate file
     *
     * @param string|null $tablename
     *
     * @since 2.0.10 Asks for a missing name instead of a TypeError, creates the folder.
     */
    protected function createMigrate(?string $tablename = '')
    {
      if ($this->isHelp($tablename)) {
        $this->info('Use php bones migrate:create <Tablename>');

        return;
      }

      if (empty($tablename)) {
        $tablename = $this->ask('Table name');
      }

      if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $tablename)) {
        $this->error('A table name is required: letters, digits and underscores.');
        exit(1);
      }

      $filename = sprintf('%s_create_%s_table.php', date('Y_m_d_His'), strtolower($tablename));

      // stubbing
      $content = $this->prepareStub('migrate', [
        '{Namespace}' => $this->getNamespace(),
        '{Tablename}' => $tablename,
      ]);

      $this->writeGeneratedFile("database/migrations/{$filename}", $content);
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

      return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    /**
     * The part every make:* command shares: validate the name, fill the stub with the plugin
     * namespace, the class and the sub-namespace of its folder, and write it under $folder.
     *
     * `Shop/Cart` becomes `{$folder}/Shop/Cart.php`, class `Cart`, and `{Path}` = `\Shop`, so the
     * namespace follows the folder (PSR-4). Up to 2.0.9 each command did this by hand, and they
     * had drifted: make:provider kept `Shop/Cart` as the class name, and six commands wrote into
     * a folder they never created.
     *
     * @since 2.0.10
     * @param string $className    The name as typed, `Name` or `Folder/Name`.
     * @param string $stub         The stub, without extension.
     * @param string $folder       Where the class goes, relative to the plugin root.
     * @param array  $replacements Extra placeholders of that stub.
     *
     * @return string The class name without its folder.
     */
    protected function generateClass(string $className, string $stub, string $folder, array $replacements = []): string
    {
      [$path, $namespacePath, $className] = $this->getPathFromAskedClass($this->validateClassName($className));

      $content = $this->prepareStub(
        $stub,
        array_merge(
          [
            '{Namespace}' => $this->getNamespace(),
            '{ClassName}' => $className,
            '{Path}' => $namespacePath,
          ],
          $replacements,
        ),
      );

      $this->writeGeneratedFile("{$folder}/{$path}{$className}.php", $content);

      return $className;
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

      $this->generateClass($this->askClassNameIfEmpty($className), 'controller', 'plugin/Http/Controllers');

      $this->optimize();
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

      // The kernel loads plugin/Console/Commands/*.php, not subfolders.
      $className = $this->validateClassName($this->askClassNameIfEmpty($className), false);

      // current plugin name and namespace
      [$pluginName, $namespace] = $this->getPluginNameAndNamespace();

      $signature = str_replace('-', '', $this->sanitize($pluginName));
      $command = str_replace('-', '', $this->sanitize($className));

      $signature = $this->ask('Enter a signature', $signature);
      $command = $this->ask('Enter the command', $command);

      // stubbing
      $content = $this->prepareStub('command', [
        '{Namespace}' => $namespace,
        '{ClassName}' => $className,
        '{Signature}' => $signature,
        '{CommandName}' => $command,
      ]);

      $this->writeGeneratedFile("plugin/Console/Commands/{$className}.php", $content);

      // check if plugin/Console/Kernel.php already exists
      if (file_exists('plugin/Console/Kernel.php')) {
        $this->info("Remember to add {$className} in the \$commands array of plugin/Console/Kernel.php");
      } else {
        // stubbing
        $content = $this->prepareStub('kernel', [
          '{Namespace}' => $namespace,
          '{ClassName}' => $className,
        ]);

        $this->writeGeneratedFile('plugin/Console/Kernel.php', $content);
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

      // Validated before the questions, so a bad name is not refused after three answers.
      $className = $this->validateClassName($this->askClassNameIfEmpty($className));

      // current plugin name
      [$pluginName] = $this->getPluginNameAndNamespace();

      $slug = str_replace('-', '_', $this->sanitize($pluginName));

      $id = $this->ask('Enter a ID', $slug);
      $name = $this->ask('Enter the name');
      $plural = $this->ask('Enter the plural name');

      if (empty($id)) {
        $id = $slug;
      }

      $class = $this->generateClass($className, 'cpt', 'plugin/CustomPostTypes', [
        '{ID}' => $id,
        '{Name}' => $name,
        '{Plural}' => $plural,
      ]);

      $this->info("Remember to add {$class} in the config/plugin.php array in the 'custom_post_types' key.");
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

      $class = $this->generateClass($this->askClassNameIfEmpty($className), 'shortcode', 'plugin/Shortcodes');

      $this->info("Remember to add {$class} in the config/plugin.php array in the 'shortcodes' key.");
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

      $class = $this->generateClass($this->askClassNameIfEmpty($className), 'schedule', 'plugin/Providers');

      $this->info("Remember to add {$class} in the config/plugin.php array in the 'providers' key.");
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

      $this->generateClass($this->askClassNameIfEmpty($className), 'provider', 'plugin/Providers');

      $this->optimize();
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

      $class = $this->generateClass($this->askClassNameIfEmpty($className), 'ajax', 'plugin/Ajax');

      $this->info("Remember to add {$class} in the config/plugin.php array in the 'ajax' key.");
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

      // Validated before the questions, so a bad name is not refused after four answers.
      $className = $this->validateClassName($this->askClassNameIfEmpty($className));

      $slug = $this->getPluginId();

      $id = $this->ask('Enter a ID', $slug);
      $name = $this->ask('Enter the name');
      $plural = $this->ask('Enter the plural name');

      $this->line('The object type below refers to the id of your previous Custom Post Type');

      $objectType = $this->ask('Enter the object type to bound');

      if (empty($id)) {
        $id = $slug;
      }

      $class = $this->generateClass($className, 'ctt', 'plugin/CustomTaxonomyTypes', [
        '{ID}' => $id,
        '{Name}' => $name,
        '{Plural}' => $plural,
        '{ObjectType}' => $objectType,
      ]);

      $this->info("Remember to add {$class} in the config/plugin.php array in the 'custom_taxonomy_types' key.");
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

      $className = $this->validateClassName($this->askClassNameIfEmpty($className));

      // current plugin name
      [$pluginName] = $this->getPluginNameAndNamespace();

      $slug = $this->getPluginId();

      // Up to 2.0.12 every widget of a plugin got the id_base "{slug}-demo-widget" and the same name,
      // so a second widget shared the first one's settings (WordPress keeps them in the option
      // widget_{id_base}). Both now come from the class: Shop/RecentPosts is "{slug}-shop-recent-posts",
      // "Recent Posts".
      $words = '/(?<=[a-z0-9])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/';
      $segments = explode('/', $className);
      $widgetId = strtolower(str_replace('_', '-', (string) preg_replace($words, '-', implode('-', $segments))));
      $widgetName = trim(str_replace('_', ' ', (string) preg_replace($words, ' ', end($segments))));

      $class = $this->generateClass($className, 'widget', 'plugin/Widgets', [
        '{PluginName}' => $pluginName,
        '{Slug}' => $slug,
        '{WidgetId}' => $widgetId,
        '{WidgetName}' => $widgetName,
      ]);

      // The views are named after the plugin, not the widget: a second widget finds them in place
      // and must not overwrite what the developer wrote in them.
      $views = [
        "resources/views/widgets/{$slug}-form.php" => '<h2>Backend form</h2>',
        "resources/views/widgets/{$slug}-index.php" => '<h2>Frontend Widget output</h2>',
      ];

      foreach ($views as $view => $html) {
        if (file_exists($view)) {
          $this->line(" Kept {$view} (shared by every widget of the plugin: --force does not overwrite it)");
          continue;
        }

        $this->writeGeneratedFile($view, $html);
      }

      $this->info("Remember to add {$class} in the config/plugin.php array in the 'widgets' key.");
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

      $this->generateClass($this->askClassNameIfEmpty($className), 'model', 'plugin/Models');

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

      $className = $this->askClassNameIfEmpty($className);

      // The table is named after the class, not its folder: Shop/Book → "book".
      $table = strtolower(basename($className));

      $this->generateClass($className, 'eloquent-model', 'plugin/Models', ['{Table}' => $table]);

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

      $this->generateClass($this->askClassNameIfEmpty($className), 'api', 'plugin/API');

      $this->optimize();
    }

    /**
     * Scaffold a React/TS app under resources/assets/apps.
     *
     * Default (folder-based, best for apps with sub-components):
     *   resources/assets/apps/<name>/index.tsx
     *
     * With --flat (single-file, best for tiny apps):
     *   resources/assets/apps/<name>.tsx
     *
     * The entry is auto-discovered by the v2 webpack config — no package.json edits needed.
     *
     * @param string|null $appName Lowercase name, dashes allowed (e.g. "dashboard", "billing-widget").
     */
    protected function createApp(?string $appName = '')
    {
      if ($this->isHelp($appName)) {
        $this->info('Usage:');
        $this->line(' php bones make:app <name> [--flat]');
        $this->info('Options:');
        $this->line(' --flat   Create a single-file app (apps/<name>.tsx) instead of folder-based');
        return;
      }

      if (empty($appName)) {
        $appName = $this->ask('App name (lowercase, e.g. "dashboard")');
      }

      if (!preg_match('/^[a-z][a-z0-9-]*$/', (string) $appName)) {
        $this->error('Invalid app name. Use lowercase letters, digits, and dashes (must start with a letter).');
        exit(1);
      }

      // Names that collide with scripts WordPress core registers. If we let
      // the user pick one of these, wp_enqueue_script silently skips our
      // bundle because the handle is already taken and the app never mounts
      // in the browser. Block the name up-front instead of letting the user
      // debug a ghost bug.
      $reservedHandles = [
        'dashboard', 'post', 'postbox', 'common', 'user', 'user-profile',
        'utils', 'admin-bar', 'admin-comments', 'media-upload', 'media-views',
        'jquery', 'jquery-core', 'jquery-ui-core', 'backbone', 'underscore',
        'react', 'react-dom', 'react-jsx-runtime',
        'wp-api', 'wp-element', 'wp-components', 'wp-data', 'wp-hooks', 'wp-i18n',
        'wp-util', 'wp-a11y', 'wp-date',
      ];
      if (in_array($appName, $reservedHandles, true)) {
        $this->error("'{$appName}' collides with a reserved WordPress script handle — wp_enqueue_script would silently drop your bundle.");
        $this->line(" Pick a different name, e.g. {$appName}-app, my-{$appName}, or a plugin-specific prefix.");
        exit(1);
      }

      $flat = in_array('--flat', $this->arguments(), true);

      // PascalCase component name (dashboard-widget → DashboardWidget)
      $componentName = str_replace(' ', '', ucwords(str_replace('-', ' ', $appName)));

      // Resolve text domain from plugin header, fall back to a placeholder the user can replace.
      $header = $this->extractPluginHeaderInfo(['Text Domain']);
      $textDomain = $header['Text Domain'] ?? 'your-text-domain';

      $content = $this->prepareStub('app', [
        '{AppName}'       => $appName,
        '{ComponentName}' => $componentName,
        '{TextDomain}'    => $textDomain,
      ]);

      $this->mkdirIfNotExists('resources/assets/apps');

      if ($flat) {
        $filepath = "resources/assets/apps/{$appName}.tsx";
        if (file_exists($filepath)) {
          $this->error("File already exists: {$filepath}");
          exit(1);
        }
        file_put_contents($filepath, $content);
        $this->line(" Created {$filepath}");
      } else {
        $folder = "resources/assets/apps/{$appName}";
        if (file_exists($folder)) {
          $this->error("Folder already exists: {$folder}");
          exit(1);
        }
        mkdir($folder, 0755, true);
        $filepath = "{$folder}/index.tsx";
        file_put_contents($filepath, $content);
        $this->line(" Created {$filepath}");
      }

      $pm     = $this->detectProjectPackageManager();
      $devCmd = $this->packageManagerCommand($pm, 'dev');

      $this->info("\nNext steps:");
      $this->line(" 1. Add the mount point to your view:");
      $this->line("    <div id=\"{$appName}-root\"></div>");
      $this->line(" 2. Enqueue it from your controller:");
      $this->line("    ->withAdminAppsScript('{$appName}')");
      $this->line(" 3. Run {$devCmd} — webpack auto-discovers the new entry.");
    }

    /**
     * Convert a 2.x plugin to the breaking changes of WP Bones 3.0. Run it after
     * `composer update wpbones/wpbones` has brought 3.0 in; running it again changes nothing.
     *
     * Migrations (wpbones/WPBones#40): each file in database/migrations/ now runs once per site,
     * so the files move to the new base class name; each seeder in database/seeders/ becomes a
     * migration, because seeders do not run any more. A seeder with $runOnce only seeds an empty
     * table, as it did.
     *
     * @since 3.0.0
     */
    protected function migrateToV3(): void
    {
      if ($this->isHelp()) {
        $this->info('Use php bones migrate:to-v3');

        return;
      }

      $this->info('WP Bones — migrate to v3');
      $this->line('');
      $this->warning('This will rewrite your database folder:');
      $this->line(' • database/migrations/*.php move to the 3.0 base class, WPBones\Database\Migration');
      $this->line(' • every database/seeders/*.php becomes a migration, and the seeder file is deleted');
      $this->line(' • it lists the pages, menus and REST routes that 3.0 gives to administrators only, the POST forms');
      $this->line('   without $plugin->csrfField(), and the Ajax providers without a nonce (nothing is rewritten)');
      $this->line('');
      $this->warning('Commit your current work first, so that git diff shows what changed.');
      $this->line('');

      // A deployed copy has no namespace file: the conversion belongs in the plugin's sources.
      if (!file_exists('namespace')) {
        $this->error('No namespace file here: run php bones migrate:to-v3 in the plugin\'s source folder, not in a deployed copy.');
        exit(1);
      }

      $answer = $this->ask('Continue? (y/N)');
      if (strtolower(trim((string) $answer)) !== 'y') {
        $this->error('Migration aborted: nothing was changed.');
        exit(1);
      }

      // An editor may have left a new line at the end of the namespace file.
      $namespace = trim($this->getNamespace());
      $old = "{$namespace}\\WPBones\\Database\\Migrations\\Migration";
      $new = "{$namespace}\\WPBones\\Database\\Migration";
      $changed = 0;
      $review = [];

      // 1. Migrations: the base class.
      foreach (glob('database/migrations/*.php') ?: [] as $file) {
        $code = (string) file_get_contents($file);
        $rewritten = str_replace(["use {$old};", "\\{$old}"], ["use {$new};", "\\{$new}"], $code);

        if ($rewritten !== $code) {
          if (file_put_contents($file, $rewritten) === false) {
            $review[] = "{$file}: could not be written, still on the 2.x base class";
            $this->warning(" Could not write {$file}");
            continue;
          }

          $this->line(" Updated {$file}");
          $changed++;
        }
      }

      // 2. Seeders: each becomes a migration that sorts after every migration there is, as 2.x ran
      // them after all of them, in the order they ran (glob order), one second apart. After today,
      // or after the latest migration when its name carries a later timestamp.
      $time = time();
      $latest = basename((string) max(glob('database/migrations/*.php') ?: ['']), '.php');

      if (preg_match('/^(\d{4})_(\d{2})_(\d{2})_(\d{2})(\d{2})(\d{2})/', $latest, $stamp)) {
        $time = max($time, (int) mktime((int) $stamp[4], (int) $stamp[5], (int) $stamp[6], (int) $stamp[2], (int) $stamp[3], (int) $stamp[1]) + 1);
      }

      foreach (glob('database/seeders/*.php') ?: [] as $file) {
        [$code, $notes] = $this->seederToMigration($file, $namespace);

        if ($code === null) {
          $review[] = "{$file}: {$notes[0]}";
          $this->warning(" Kept {$file}: {$notes[0]}");
          continue;
        }

        // BookSeeder → book_seeder: the name says where it came from.
        $name = strtolower((string) preg_replace('/(?<=[a-z0-9])[A-Z]|(?<=[A-Z])[A-Z](?=[a-z])/', '_$0', basename($file, '.php')));
        $target = sprintf('database/migrations/%s_%s.php', date('Y_m_d_His', $time++), $name);

        if (!is_dir('database/migrations')) {
          mkdir('database/migrations', 0755, true);
        }

        if ($latest !== '' && strcmp(basename($target, '.php'), $latest) <= 0) {
          $review[] = "{$target}: it sorts before {$latest}, which 2.x ran first: rename it so that it comes after";
        }

        // The seeder goes only once its migration is on disk and parses: nothing is lost.
        if (file_put_contents($target, $code) === false) {
          $review[] = "{$file}: kept, {$target} could not be written";
          $this->warning(" Kept {$file}: could not write {$target}");
          continue;
        }

        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($target) . ' 2>&1', $lint, $status);

        if ($status !== 0) {
          unlink($target);
          $review[] = "{$file}: kept, the migration made from it did not parse (" . trim(implode(' ', $lint)) . '): convert it by hand';
          $this->warning(" Kept {$file}: the migration made from it did not parse");
          continue;
        }

        unlink($file);
        $this->line(" Converted {$file} into {$target}");
        $changed++;

        foreach ($notes as $note) {
          $review[] = "{$target}: {$note}";
        }
      }

      if (is_dir('database/seeders') && (glob('database/seeders/*') ?: []) === []) {
        rmdir('database/seeders');
      }

      // 3. Who may open what: listed, never rewritten.
      $review = array_merge($review, $this->accessChangesInV3());

      $this->line('');

      if ($changed === 0 && $review === []) {
        $this->success('Nothing to change: this plugin is already on the 3.0 layout.');

        return;
      }

      $this->success('Migration to v3 complete.');

      if ($review !== []) {
        $this->line('');
        $this->warning('Review manually:');
        foreach ($review as $line) {
          $this->line(" • {$line}");
        }
      }

      $this->line('');
      $this->info('Next steps:');
      $this->line(' 1. git diff, and php -l on the new files');
      $this->line(' 2. php bones migrate:status on a site that runs the plugin');
    }

    /**
     * The pages, menus and REST routes that declare nothing, which 2.x opened to every logged-in user
     * (or, for a REST route, to everyone) and 3.0 gives to administrators only: one line each. Read
     * from the tokens, not by including the files, which need WordPress.
     *
     * @since 3.0.0
     */
    protected function accessChangesInV3(): array
    {
      $lines = [];
      $read = "'read' keeps it open to every logged-in user";

      foreach (['routes' => 'page', 'menus' => 'menu'] as $config => $what) {
        $file = "config/{$config}.php";

        if (!is_file($file)) {
          continue;
        }

        $entries = $this->configEntries((string) file_get_contents($file));

        if ($entries === null) {
          $lines[] = "{$file} does not return a literal array, so it was not read: since 3.0 a {$what} without a capability needs manage_options ({$read}), check them by hand";
          continue;
        }

        foreach ($entries as $key => $keys) {
          if (!in_array('capability', $keys, true)) {
            $lines[] = "{$file}: the {$what} {$key} declares no capability, so since 3.0 it needs manage_options ({$read})"
              . ($what === 'menu' ? '; declare it on the menu itself: with read on an item only, a subscriber sees the menu and cannot open its first page' : '');
          }
        }
      }

      foreach (glob('pages/*.php') ?: [] as $file) {
        if (!$this->declaresPageCapability((string) file_get_contents($file))) {
          $lines[] = "{$file} declares no capability() (public, without required arguments), so since 3.0 it needs manage_options (returning {$read}; ignore this line if it inherits one)";
        }
      }

      // RestProvider loads the routes from api.custom.path, /api by default.
      $api = 'api';

      if (is_file('config/api.php')) {
        $path = $this->customApiPath((string) file_get_contents('config/api.php'));

        if ($path === false) {
          $lines[] = "config/api.php: the REST route folder is not a literal path, so only api/ was read: check the routes elsewhere by hand";
        } elseif ($path !== null) {
          $api = trim($path, '/') ?: 'api';
        }
      }

      if (is_dir($api)) {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($api, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $entry) {
          if ($entry->getExtension() !== 'php') {
            continue;
          }

          $file = str_replace(DIRECTORY_SEPARATOR, '/', $entry->getPathname());

          foreach ($this->routesWithoutPermission((string) file_get_contents($entry->getPathname())) as [$line, $call, $literal]) {
            $lines[] = $literal
              ? "{$file}:{$line}: {$call} has no permission_callback, so since 3.0 it refuses every request ('permission_callback' => '__return_true' keeps it public)"
              : "{$file}:{$line}: {$call} passes options that are not a literal array: since 3.0 a route without a permission_callback refuses every request, check it by hand";
          }
        }
      }

      // Since 3.0 a request to an admin page that is not a GET carries the plugin's nonce: each POST
      // form, in the views and in the pages/ classes that print their own, holds csrfField().
      foreach (['resources/views', 'pages'] as $folder) {
        if (!is_dir($folder)) {
          continue;
        }

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $entry) {
          if ($entry->getExtension() !== 'php') {
            continue;
          }

          $code = (string) file_get_contents($entry->getPathname());
          $file = str_replace(DIRECTORY_SEPARATOR, '/', $entry->getPathname());

          // A pages/ class that opts out (a public csrf() returning false) is not guarded.
          if ($folder === 'pages' && preg_match('/\bfunction\s+csrf\s*\(\s*\)[^{]*\{\s*return\s+false\s*;/i', $code)) {
            continue;
          }

          foreach ($this->postForms($code, $folder === 'pages') as $at) {
            // The form's own markup: up to its </form>, or the end of the file.
            $close = stripos($code, '</form', $at);
            $body = substr($code, $at, $close === false ? null : $close - $at);

            if (strpos($body, 'csrfField(') !== false) {
              continue;
            }

            $line = substr_count(substr($code, 0, $at), "\n") + 1;
            $lines[] = "{$file}:{$line}: a POST form without \$plugin->csrfField(): since 3.0 the page refuses the request (a page that checks a nonce of its own may say 'csrf' => false)";
          }
        }
      }

      // Since 3.0 a logged Ajax action needs a nonce to check, and useHTTPPost() unslashes.
      if (is_dir('plugin')) {
        $classes = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator('plugin', \FilesystemIterator::SKIP_DOTS));

        foreach ($classes as $entry) {
          if ($entry->getExtension() !== 'php') {
            continue;
          }

          $code = (string) file_get_contents($entry->getPathname());
          $file = str_replace(DIRECTORY_SEPARATOR, '/', $entry->getPathname());

          // Only a direct child of the framework's provider, under its name or the one a use
          // statement gives it: one of the plugin's own base classes may set $nonceHash for all its
          // children, which a file alone cannot show.
          $names = ['WordPressAjaxServiceProvider'];

          if (preg_match_all('/\buse\s+[\w\\\\]*\\\\WordPressAjaxServiceProvider\s+as\s+(\w+)\s*;/i', $code, $aliases)) {
            $names = array_merge($names, $aliases[1]);
          }

          $direct = (bool) preg_match('/\bextends\s+\\\\?(?:[\w\\\\]+\\\\)?(?:' . implode('|', array_map('preg_quote', $names)) . ')\b/', $code);

          if ($direct
            && preg_match('/\$logged\s*=\s*(?:\[|array\s*\()\s*[\'"]/i', $code)
            && !preg_match('/\$nonceHash\s*=\s*[\'"][^\'"]+[\'"]/', $code)) {
            $lines[] = "{$file} has logged Ajax actions and no \$nonceHash: since 3.0 they refuse every request until it sets one and its requests send the nonce";
          }

          if (strpos($code, 'useHTTPPost(') !== false && preg_match_all('/\b(?:stripslashes|wp_unslash)\s*\(/', $code, $calls, PREG_OFFSET_CAPTURE)) {
            foreach ($calls[0] as [, $at]) {
              $line = substr_count(substr($code, 0, $at), "\n") + 1;
              $lines[] = "{$file}:{$line}: unslashes what useHTTPPost() returns? Since 3.0 it comes unslashed: a second pass corrupts quotes and backslashes";
            }
          }
        }
      }

      return $lines;
    }

    /**
     * The offsets of the POST forms a file renders. In a view only its HTML counts: a form inside a
     * PHP string there is an example printed as text (htmlentities()), not one a browser submits.
     * A pages/ class renders the strings it returns, so there they count too.
     */
    protected function postForms(string $code, bool $inStrings): array
    {
      // Where the markup is: a view's HTML, and a pages/ class's strings.
      $kinds = $inStrings ? [T_INLINE_HTML, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE] : [T_INLINE_HTML];
      $ranges = [];
      $offset = 0;

      foreach (token_get_all($code) as $token) {
        $text = is_array($token) ? $token[1] : $token;

        if (is_array($token) && in_array($token[0], $kinds, true)) {
          $ranges[] = [$offset, $offset + strlen($text)];
        }

        $offset += strlen($text);
      }

      // A tag may hold PHP of its own, an echo in its action: the > that closes the PHP does not
      // close the tag. (Not quoted here: a PHP close tag ends a // comment.)
      preg_match_all('/<form\b(?:<\?(?:php|=).*?\?>|[^>])*>/is', $code, $forms, PREG_OFFSET_CAPTURE);

      $found = [];

      foreach ($forms[0] as [$tag, $at]) {
        $inMarkup = false;

        foreach ($ranges as [$from, $to]) {
          if ($at >= $from && $at < $to) {
            $inMarkup = true;
            break;
          }
        }

        if ($inMarkup && preg_match('/\bmethod\s*=\s*\\\\?["\']?post\b/i', $tag)) {
          $found[] = $at;
        }
      }

      return $found;
    }

    /**
     * The entries of the array a config file returns, each with the string keys it holds itself (not
     * those of the arrays inside it): ['my_page' => ['title', 'capability', 'route']]. A `capability`
     * of null or '' is left out, as the providers fall back to the default for it.
     *
     * Null when the file cannot be read this way: it returns something else than a literal array (a
     * variable, a call), it has more than one top-level return, or an entry's key is not a literal
     * string (a constant, an interpolation).
     */
    protected function configEntries(string $code): ?array
    {
      $tokens = $this->codeTokens($code);
      $count = count($tokens);

      // The file's own return: not one inside a block, such as an ABSPATH guard's.
      $returns = [];
      $braces = 0;

      foreach ($tokens as $i => $token) {
        if ($token === '{' || (is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
          $braces++;
        } elseif ($token === '}') {
          $braces--;
        } elseif ($braces === 0 && is_array($token) && $token[0] === T_RETURN) {
          $returns[] = $i;
        }
      }

      if (count($returns) !== 1) {
        return null;
      }

      $i = $returns[0];
      $first = $tokens[$i + 1] ?? null;

      if (!($first === '[' || (is_array($first) && $first[0] === T_ARRAY))) {
        return null;
      }

      $entries = [];
      $current = null;
      $depth = 0;

      for ($i++; $i < $count; $i++) {
        $token = $tokens[$i];
        $text = is_array($token) ? $token[1] : $token;

        if (in_array($text, ['[', '(', '{'], true) || (is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES, T_ATTRIBUTE], true))) {
          $depth++;
          continue;
        }

        if (in_array($text, [']', ')', '}'], true)) {
          if (--$depth === 0) {
            break;
          }
          continue;
        }

        if ($text === ';' && $depth === 0) {
          break;
        }

        // An entry's key that is not a literal string cannot be named, nor its keys told apart.
        if ($depth === 1 && is_array($token) && $token[0] === T_DOUBLE_ARROW) {
          $before = $tokens[$i - 1];

          if (!(is_array($before) && $before[0] === T_CONSTANT_ENCAPSED_STRING)) {
            return null;
          }
        }

        $isKey = is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING
          && is_array($tokens[$i + 1] ?? null) && $tokens[$i + 1][0] === T_DOUBLE_ARROW;

        if (!$isKey) {
          continue;
        }

        $key = substr($token[1], 1, -1);

        if ($depth === 1) {
          $current = $key;
          $entries[$key] = [];
        } elseif ($depth === 2 && $current !== null) {
          if ($key === 'capability' && $this->isEmptyValue($tokens, $i + 2)) {
            continue;
          }

          $entries[$current][] = $key;
        }
      }

      return $entries;
    }

    /**
     * Whether the value starting at $at is a bare null or an empty string, followed by the end of its
     * element.
     */
    protected function isEmptyValue(array $tokens, int $at): bool
    {
      $value = $tokens[$at] ?? null;
      $after = $tokens[$at + 1] ?? null;

      if (!in_array($after, [',', ']', ')'], true) || !is_array($value)) {
        return false;
      }

      return ($value[0] === T_STRING && strtolower($value[1]) === 'null')
        || ($value[0] === T_CONSTANT_ENCAPSED_STRING && strlen($value[1]) === 2);
    }

    /**
     * Whether a pages/ class declares a capability() that the provider calls: public, by its own
     * name, and callable without arguments. One it inherits cannot be seen from here.
     */
    protected function declaresPageCapability(string $code): bool
    {
      $tokens = $this->codeTokens($code);
      $count = count($tokens);

      foreach ($tokens as $i => $token) {
        if (!is_array($token) || $token[0] !== T_FUNCTION) {
          continue;
        }

        $at = ($tokens[$i + 1] ?? null) === '&' ? $i + 2 : $i + 1;
        $name = $tokens[$at] ?? null;

        if (!is_array($name) || strcasecmp($name[1], 'capability') !== 0 || ($tokens[$at + 1] ?? null) !== '(') {
          continue;
        }

        for ($k = $i - 1; $k >= 0 && is_array($tokens[$k]) && in_array($tokens[$k][0], [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_FINAL, T_ABSTRACT], true); $k--) {
          if (in_array($tokens[$k][0], [T_PROTECTED, T_PRIVATE], true)) {
            return false;
          }
        }

        // Count the parameters without a default value.
        $required = 0;
        $depth = 0;
        $param = $default = $variadic = false;

        for ($j = $at + 2; $j < $count; $j++) {
          $text = is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];

          if ($depth === 0 && ($text === ')' || $text === ',')) {
            $required += $param && !$default && !$variadic ? 1 : 0;
            $param = $default = $variadic = false;

            if ($text === ')') {
              break;
            }
            continue;
          }

          if (in_array($text, ['(', '['], true)) {
            $depth++;
          } elseif (in_array($text, [')', ']'], true)) {
            $depth--;
          } elseif ($depth === 0 && $text === '=') {
            $default = true;
          } elseif ($depth === 0 && is_array($tokens[$j]) && $tokens[$j][0] === T_ELLIPSIS) {
            $variadic = true;
          } elseif ($depth === 0 && is_array($tokens[$j]) && $tokens[$j][0] === T_VARIABLE) {
            $param = true;
          }
        }

        return $required === 0;
      }

      return false;
    }

    /**
     * The custom.path of config/api.php: the string when it is one whole literal, false when it is
     * something else (a concatenation, a constant), null when there is none.
     *
     * @return string|false|null
     */
    protected function customApiPath(string $code)
    {
      $tokens = $this->codeTokens($code);
      $count = count($tokens);
      $depth = 0;
      $inCustom = false;

      for ($i = 0; $i < $count; $i++) {
        $text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];

        if ($text === '[' || $text === '(') {
          $depth++;
          continue;
        }

        if ($text === ']' || $text === ')') {
          $depth--;
          if ($inCustom && $depth < 2) {
            $inCustom = false;
          }
          continue;
        }

        $isKey = is_array($tokens[$i]) && $tokens[$i][0] === T_CONSTANT_ENCAPSED_STRING
          && is_array($tokens[$i + 1] ?? null) && $tokens[$i + 1][0] === T_DOUBLE_ARROW;

        if (!$isKey) {
          continue;
        }

        $key = substr($tokens[$i][1], 1, -1);

        if ($depth === 1 && $key === 'custom') {
          $inCustom = true;
        } elseif ($inCustom && $depth === 2 && $key === 'path') {
          $value = $tokens[$i + 2] ?? null;
          $after = $tokens[$i + 3] ?? null;
          $literal = is_array($value) && $value[0] === T_CONSTANT_ENCAPSED_STRING && in_array($after, [',', ']', ')'], true);

          return $literal ? stripslashes(substr($value[1], 1, -1)) : false;
        }
      }

      return null;
    }

    /**
     * The Route:: calls in an API route file that pass no permission_callback, as [line, call,
     * literal]: literal is false when the options are not a literal array, which cannot be read. Only a
     * key of the options counts, not the string anywhere in the call, and a null value is no callback.
     * The class may be imported under another name (`use …\Route as Api`), in any case; a call nested
     * in another's arguments is read too.
     */
    protected function routesWithoutPermission(string $code): array
    {
      $tokens = $this->codeTokens($code);
      $count = count($tokens);
      $found = [];

      // The names Route goes by in this file: its own, and the aliases a use statement gives it.
      $names = ['route'];

      foreach ($tokens as $i => $token) {
        if (is_array($token) && $token[0] === T_AS
          && is_array($tokens[$i - 1] ?? null) && preg_match('/(^|\\\\)Route$/i', $tokens[$i - 1][1])
          && is_array($tokens[$i + 1] ?? null) && $tokens[$i + 1][0] === T_STRING) {
          $names[] = strtolower($tokens[$i + 1][1]);
        }
      }

      for ($i = 0; $i + 3 < $count; $i++) {
        $class = is_array($tokens[$i]) && in_array($tokens[$i][0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)
          ? strtolower(ltrim(strrchr('\\' . $tokens[$i][1], '\\'), '\\'))
          : null;

        $isCall = $class !== null && in_array($class, $names, true)
          && is_array($tokens[$i + 1]) && $tokens[$i + 1][0] === T_DOUBLE_COLON
          && is_array($tokens[$i + 2]) && in_array(strtolower($tokens[$i + 2][1]), ['get', 'post', 'put', 'patch', 'delete', 'request'], true)
          && $tokens[$i + 3] === '(';

        if (!$isCall) {
          continue;
        }

        $method = strtolower($tokens[$i + 2][1]);
        $depth = 0;
        $argument = 0;
        $path = null;
        $permission = false;
        // The options are the third argument, the fourth of request(): null while none is seen.
        $optionsAt = $method === 'request' ? 3 : 2;
        $options = null;

        for ($j = $i + 3; $j < $count; $j++) {
          $token = $tokens[$j];
          $text = is_array($token) ? $token[1] : $token;

          if ($depth === 1 && $argument === $optionsAt && $options === null && $text !== ',' && $text !== ')') {
            $options = $text === '[' || (is_array($token) && $token[0] === T_ARRAY) ? 'literal' : 'other';
          }

          if (in_array($text, ['(', '[', '{'], true) || (is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES, T_ATTRIBUTE], true))) {
            $depth++;
          } elseif (in_array($text, [')', ']', '}'], true)) {
            if (--$depth === 0) {
              break;
            }
          } elseif ($text === ',' && $depth === 1) {
            $argument++;
          } elseif (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
            // The path is the first argument, or the second of request(), whose first is the verbs.
            if ($path === null && $depth === 1 && $argument === ($method === 'request' ? 1 : 0)) {
              $path = $token[1];
            }

            $isKey = is_array($tokens[$j + 1] ?? null) && $tokens[$j + 1][0] === T_DOUBLE_ARROW;

            if ($argument === $optionsAt && $depth === 2 && $isKey && substr($token[1], 1, -1) === 'permission_callback'
              && !$this->isEmptyValue($tokens, $j + 2)) {
              $permission = true;
            }
          }
        }

        if ($options === 'other') {
          $found[] = [$tokens[$i][2], 'Route::' . $method . '(' . ($path ?? '…') . ')', false];
        } elseif (!$permission) {
          $found[] = [$tokens[$i][2], 'Route::' . $method . '(' . ($path ?? '…') . ')', true];
        }
      }

      return $found;
    }

    /**
     * The tokens of a PHP source without whitespace and comments.
     */
    protected function codeTokens(string $code): array
    {
      return array_values(array_filter(
        token_get_all($code),
        fn($token) => !is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)
      ));
    }

    /**
     * A 2.x seeder rewritten as a 3.0 migration: the body of run() becomes up(), and the helpers
     * that used the seeder's own table (insert(), truncate(), count()) name it.
     *
     * @return array{0: ?string, 1: string[]} The migration's code, or null when the seeder is not
     *                                        one this can convert, and the notes for the review.
     */
    protected function seederToMigration(string $file, string $namespace): array
    {
      $source = (string) file_get_contents($file);
      $tokens = token_get_all($source);

      // The seeder's settings, read from its code and not from its comments: a commented-out
      // `$runOnce = true;` must not add a guard.
      $code = '';
      foreach ($tokens as $token) {
        if (!is_array($token) || !in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
          $code .= is_array($token) ? $token[1] : $token;
        }
      }

      $table = preg_match('/\$tablename\s*=\s*([\'"])([A-Za-z0-9_]+)\1\s*;/', $code, $m) ? $m[2] : null;
      $usePrefix = !preg_match('/\$usePrefix\s*=\s*false\s*;/i', $code);
      $runOnce = (bool) preg_match('/\$runOnce\s*=\s*true\s*;/i', $code);
      $fileNamespace = preg_match('/^\s*namespace\s+([A-Za-z0-9_\\\\]+)\s*;/m', $code, $m) ? $m[1] : null;

      // The methods it declares, and where run() begins and ends.
      $methods = [];
      $body = null;
      $count = count($tokens);

      for ($i = 0; $i < $count; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
          continue;
        }

        $j = $i + 1;
        while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
          $j++;
        }

        if (!is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING) {
          continue; // a closure
        }

        $methods[] = $tokens[$j][1];

        if ($tokens[$j][1] !== 'run' || $body !== null) {
          continue;
        }

        // The opening brace of run(), then its match.
        while ($j < $count && $tokens[$j] !== '{') {
          $j++;
        }

        $depth = 0;
        for ($k = $j; $k < $count; $k++) {
          $text = is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];

          if ($tokens[$k] === '{' || (is_array($tokens[$k]) && in_array($tokens[$k][0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
            $depth++;
          } elseif ($tokens[$k] === '}') {
            $depth--;

            if ($depth === 0) {
              $body = array_slice($tokens, $j + 1, $k - $j - 1);
              break;
            }
          }
        }
      }

      if ($body === null) {
        return [null, ['no run() method found: convert it by hand']];
      }

      if ($methods !== ['run']) {
        return [null, ['it declares methods besides run(): convert it by hand']];
      }

      // The body, with the table named where the 2.x helpers took it for granted.
      $code = '';
      $usesTable = false;
      $usesWpdb = false;
      $needsTable = false;
      $truncatesByName = false;
      $n = count($body);

      // The index of the next token that is not white space.
      $next = function (int $from) use ($body, $n): int {
        while ($from < $n && is_array($body[$from]) && $body[$from][0] === T_WHITESPACE) {
          $from++;
        }

        return $from;
      };

      for ($i = 0; $i < $n; $i++) {
        $token = $body[$i];
        $text = is_array($token) ? $token[1] : $token;

        if (is_array($token) && $token[0] === T_VARIABLE && $text === '$this') {
          // $this->name, also spelled `$this -> name` or `$this?->name`
          $j = $next($i + 1);
          $arrow = $j < $n && is_array($body[$j]) && in_array($body[$j][0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true);
          $at = $arrow ? $next($j + 1) : $n;
          $member = $at < $n && is_array($body[$at]) && $body[$at][0] === T_STRING ? $body[$at][1] : null;

          if ($member === 'tablename') {
            $usesTable = true;
          }

          if ($member === 'wpdb') {
            $usesWpdb = true;
          }

          if (in_array($member, ['insert', 'truncate', 'count'], true)) {
            $k = $at + 1;
            while ($k < $n && is_array($body[$k]) && $body[$k][0] === T_WHITESPACE) {
              $k++;
            }

            if ($k < $n && $body[$k] === '(') {
              $m = $k + 1;
              while ($m < $n && is_array($body[$m]) && $body[$m][0] === T_WHITESPACE) {
                $m++;
              }

              if ($member === 'truncate' && $body[$m] !== ')') {
                $truncatesByName = true;
              }

              // insert($sql) gains the table first; truncate() and count() only when called bare.
              if ($member === 'insert' || $body[$m] === ')') {
                $needsTable = true;
                // No space before a line break: the argument may start on the next line.
                $separator = $body[$m] === ')' ? '' : ($m > $k + 1 && str_contains($body[$k + 1][1], "\n") ? ',' : ', ');
                $code .= "\$this->{$member}(" . var_export((string) $table, true) . $separator;
                $i = $k;
                continue;
              }
            }
          }
        }

        $code .= $text;
      }

      if (($needsTable || $usesTable || $runOnce) && $table === null) {
        return [null, ['it uses its table but sets no $tablename: convert it by hand']];
      }

      $notes = [];
      $properties = '';
      $prelude = '';

      if (!$usePrefix) {
        $properties .= "  protected \$usePrefix = false;\n\n";
      }

      if ($usesTable) {
        $properties .= "  /** The prefixed table name, as the 2.x seeder had it in \$this->tablename. */\n  protected \$tablename;\n\n";
        $prelude .= "    \$this->tablename = \$this->table('{$table}');\n\n";
      }

      if ($usesWpdb) {
        $properties .= "  /** The wpdb object, as the 2.x seeder had it in \$this->wpdb. */\n  protected \$wpdb;\n\n";
        $prelude .= "    \$this->wpdb = \$GLOBALS['wpdb'];\n\n";
      }

      if (!$usePrefix && $truncatesByName) {
        $notes[] = "it calls truncate() with a table name and has \$usePrefix = false: 2.x prefixed that name anyway, a migration does not, so check which table it empties";
      }

      if ($runOnce) {
        $prelude .= "    // The seeder had \$runOnce: it seeded the table only while it was empty, and so does this.\n";
        $prelude .= "    if (!\$this->isEmpty('{$table}')) {\n      return;\n    }\n\n";
      } else {
        $notes[] = 'it ran on every activation and update until 2.x and runs once now: if it refreshed reference data, refresh it with a new migration when the data changes';
      }

      // The use statements the seeder had, but its base class.
      $uses = [];
      foreach (preg_split('/\R/', $source) as $line) {
        if (preg_match('/^use\s+([^;]+);/', trim($line), $use) && !preg_match('/\\\\WPBones\\\\Database\\\\Seeder$/', trim($use[1]))) {
          $uses[] = trim($line);
        }
      }

      // A namespace declaration has to come first, before the ABSPATH guard.
      $migration = "<?php\n\n" . ($fileNamespace === null ? '' : "namespace {$fileNamespace};\n\n");
      $migration .= "if (!defined('ABSPATH')) {\n  exit();\n}\n\n";
      $migration .= "use {$namespace}\\WPBones\\Database\\Migration;\n";
      $migration .= $uses === [] ? '' : implode("\n", $uses) . "\n";
      $migration .= "\n/*\n * Converted from " . $file . " by php bones migrate:to-v3.\n */\n";
      $migration .= "return new class extends Migration {\n";
      $migration .= $properties;
      $migration .= "  public function up()\n  {\n";
      $migration .= $prelude;
      $migration .= rtrim(ltrim($code, "\r\n"), " \t\r\n") . "\n";
      $migration .= "  }\n};\n";

      return [$migration, $notes];
    }

    /**
     * Migrate a v1.x plugin (gulp + run-s + wp-scripts split) to the v2 unified
     * webpack infrastructure.
     *
     * The migration:
     *  - Deletes gulpfile.js, package-lock.json and pnpm-lock.yaml so the
     *    developer can pick up a clean lockfile with their preferred PM
     *    (yarn.lock is left untouched if already present)
     *  - Creates webpack.config.js, tsconfig.json, .prettierrc, .prettierignore, jest.config.js
     *  - Rewrites package.json scripts to the unified dev/build/test/format block
     *  - Drops gulp-* and npm-run-all devDependencies
     *  - Adds the v2 devDependency set (@wordpress/scripts 31+, typescript, glob,
     *    less/less-loader, webpack-remove-empty-scripts, @wordpress/jest-preset-default,
     *    @types/react, @types/react-dom, prettier as wp-prettier)
     *
     * The migration does NOT touch resources/assets/ — the developer's code stays
     * as-is. The printed "Next steps" suggest install/build/test commands using
     * the package manager detected from the project lockfile before deletion
     * (yarn-first default when no lockfile is present).
     *
     * @since 2.0.0
     */
    protected function migrateToV2(): void
    {
      $this->info('WP Bones — migrate to v2');
      $this->line('');
      $this->warning('This will modify your plugin build infrastructure:');
      $this->line(' • Delete gulpfile.js, package-lock.json and pnpm-lock.yaml (yarn.lock is kept)');
      $this->line(' • Create webpack.config.js, tsconfig.json, .prettierrc, .prettierignore, jest.config.js');
      $this->line(' • Rewrite package.json scripts and devDependencies');
      $this->line('');
      $this->warning('Commit your current work first. The resources/assets/ folder is left untouched.');
      $this->line('');

      $answer = $this->ask('Continue? (y/N)');
      if (strtolower(trim($answer)) !== 'y') {
        $this->error('Migration aborted: nothing was changed.');
        exit(1);
      }

      // Detect the developer's PM *before* we delete their lockfile, so the
      // "Next steps" we print at the end match the tooling they actually use.
      $projectPm = $this->detectProjectPackageManager();

      // 1. Delete gulp-era files
      foreach (['gulpfile.js', 'package-lock.json', 'pnpm-lock.yaml'] as $file) {
        if (file_exists($file)) {
          unlink($file);
          $this->line(" Removed {$file}");
        }
      }

      // 2. Create v2 config files (skip if already present — don't clobber customizations)
      $configs = [
        'webpack.config.js' => 'webpack-config',
        'tsconfig.json'     => 'tsconfig',
        '.prettierrc'       => 'prettierrc',
        '.prettierignore'   => 'prettierignore',
        'jest.config.js'    => 'jest-config',
      ];
      foreach ($configs as $file => $stub) {
        if (file_exists($file)) {
          $this->warning(" Kept existing {$file} (review manually)");
          continue;
        }
        file_put_contents($file, $this->prepareStub($stub, []));
        $this->line(" Created {$file}");
      }

      // 3. Rewrite package.json
      if (file_exists('package.json')) {
        $pkg = json_decode(file_get_contents('package.json'), true);
        if (!is_array($pkg)) {
          $this->error('package.json is not valid JSON, skipping rewrite.');
        } else {
          $previousScripts = $pkg['scripts'] ?? [];

          $pkg['scripts'] = [
            'dev'             => 'wp-scripts start',
            'build'           => 'wp-scripts build',
            'test'            => 'wp-scripts test-unit-js',
            'test:watch'      => 'wp-scripts test-unit-js --watch',
            'format'          => 'wp-scripts format',
            // `wp-scripts format` drops --check and always writes (scripts/format.js, 31.8.0): up to
            // 2.0.12 this "check" rewrote the files, the compiled bundles included, and exited 0.
            // Same files, same ignore file, same Prettier, and nothing written.
            'format:check'    => 'prettier --check --ignore-path .prettierignore "**/*.{js,jsx,json,ts,tsx,yml,yaml}"',
            'lint'            => 'wp-scripts lint-js resources/',
            'lint:style'      => "wp-scripts lint-style 'resources/**/*.{css,scss}'",
            'check-engines'   => 'wp-scripts check-engines',
            'check-licenses'  => 'wp-scripts check-licenses',
            'packages-update' => 'wp-scripts packages-update',
          ];

          // Preserve make-pot / make-json if the plugin had them
          foreach (['make-pot', 'make-json'] as $preserve) {
            if (isset($previousScripts[$preserve])) {
              $pkg['scripts'][$preserve] = $previousScripts[$preserve];
            }
          }

          // Drop gulp-era devDeps
          $dropDevDeps = [
            '@babel/core', '@babel/preset-env', '@babel/preset-react',
            'gulp', 'gulp-babel', 'gulp-clean-css', 'gulp-less',
            'gulp-sass', 'gulp-typescript', 'gulp-uglify', 'gulp-watch',
            'sass', 'npm-run-all',
          ];
          foreach ($dropDevDeps as $dep) {
            unset($pkg['devDependencies'][$dep]);
          }

          // v2-required devDeps — always overwritten so an old pinned version
          // (e.g. @wordpress/scripts ^27) is bumped to the range v2 needs.
          $addDevDeps = [
            '@types/react'                     => '^18.3.0',
            '@types/react-dom'                 => '^18.3.0',
            '@wordpress/jest-preset-default'   => '^12.44.0',
            '@wordpress/scripts'               => '^31.7.0',
            'glob'                             => '^11.0.0',
            'less'                             => '^4.6.4',
            'less-loader'                      => '^12.2.0',
            // format:check runs `prettier` itself, and pnpm 12 links only the binaries of direct
            // dependencies (measured: 12.6.0 answered "prettier: command not found", 10.34.5 did
            // not). The alias is the one @wordpress/scripts 31 declares, and the one its `format`
            // asks projects to install.
            'prettier'                         => 'npm:wp-prettier@3.0.3',
            'typescript'                       => '^5.9.3',
            'webpack-remove-empty-scripts'     => '^1.1.0',
          ];
          foreach ($addDevDeps as $dep => $version) {
            $pkg['devDependencies'][$dep] = $version;
          }

          if (isset($pkg['devDependencies'])) {
            ksort($pkg['devDependencies']);
          }
          if (isset($pkg['dependencies'])) {
            ksort($pkg['dependencies']);
          }

          // npm and yarn indent package.json with two spaces; JSON_PRETTY_PRINT writes four, which
          // turned the migration into a whole-file diff. Halve the leading indentation back.
          $json = json_encode($pkg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
          $json = preg_replace_callback('/^(?: {4})+/m', function ($match) {
            return str_repeat('  ', strlen($match[0]) / 4);
          }, $json);
          file_put_contents('package.json', $json . "\n");
          $this->line(' Rewrote package.json');
        }
      }

      // 4. Summary + manual steps
      $installCmd = $this->packageManagerCommand($projectPm, 'install');
      $buildCmd   = $this->packageManagerCommand($projectPm, 'build');
      $testCmd    = $this->packageManagerCommand($projectPm, 'test');

      $this->line('');
      $this->success('Migration to v2 complete.');
      $this->line('');
      $this->info("Next steps ({$projectPm}):");
      $this->line(" 1. {$installCmd}");
      $this->line(" 2. {$buildCmd}     # verify everything compiles");
      $this->line(" 3. {$testCmd}      # verify tests still pass, if any");
      $this->line('');
      if ($projectPm !== 'yarn') {
        $this->line("(Your {$projectPm} lockfile was removed during migration. To switch to yarn, run `yarn install` instead of step 1.)");
        $this->line('');
      }

      $this->warning('Review manually:');
      $this->line(' • Custom gulp tasks (if you had any) must be re-implemented as webpack plugins');
      $this->line(' • Old build:<name> / start:<name> scripts for individual apps are gone — ');
      $this->line('   webpack auto-discovers everything under resources/assets/apps/.');
      $this->line(' • File extensions: .jsx/.tsx files in apps/ are fine; plain .js with JSX must be renamed.');
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

