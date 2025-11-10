<?php

namespace WPKirk\WPBones\Console;

use WPKirk\WPBones\Support\Traits\HasAttributes;

// Standard Color Definitions
define('WPBONES_KERNEL_COLOR_BLACK', "\033[0;30m");
define('WPBONES_KERNEL_COLOR_RED', "\033[0;31m");
define('WPBONES_KERNEL_COLOR_GREEN', "\033[0;32m");
define('WPBONES_KERNEL_COLOR_YELLOW', "\033[0;33m");
define('WPBONES_KERNEL_COLOR_BLUE', "\033[0;34m");
define('WPBONES_KERNEL_COLOR_MAGENTA', "\033[0;35m");
define('WPBONES_KERNEL_COLOR_CYAN', "\033[0;36m");
define('WPBONES_KERNEL_COLOR_WHITE', "\033[0;37m");

// Definition of bold colors
define('WPBONES_KERNEL_COLOR_BOLD_BLACK', "\033[1;30m");
define('WPBONES_KERNEL_COLOR_BOLD_RED', "\033[1;31m");
define('WPBONES_KERNEL_COLOR_BOLD_GREEN', "\033[1;32m");
define('WPBONES_KERNEL_COLOR_BOLD_YELLOW', "\033[1;33m");
define('WPBONES_KERNEL_COLOR_BOLD_BLUE', "\033[1;34m");
define('WPBONES_KERNEL_COLOR_BOLD_MAGENTA', "\033[1;35m");
define('WPBONES_KERNEL_COLOR_BOLD_CYAN', "\033[1;36m");
define('WPBONES_KERNEL_COLOR_BOLD_WHITE', "\033[1;37m");

// Definition of Light Colors
define('WPBONES_KERNEL_COLOR_LIGHT_BLACK', "\033[0;38;5;240m");
define('WPBONES_KERNEL_COLOR_LIGHT_RED', "\033[0;38;5;203m");
define('WPBONES_KERNEL_COLOR_LIGHT_GREEN', "\033[0;38;5;82m");
define('WPBONES_KERNEL_COLOR_LIGHT_YELLOW', "\033[0;38;5;227m");
define('WPBONES_KERNEL_COLOR_LIGHT_BLUE', "\033[0;38;5;117m");
define('WPBONES_KERNEL_COLOR_LIGHT_MAGENTA', "\033[0;38;5;213m");
define('WPBONES_KERNEL_COLOR_LIGHT_CYAN', "\033[0;38;5;159m");
define('WPBONES_KERNEL_COLOR_LIGHT_WHITE', "\033[0;38;5;15m");

// Definition for color reset
define('WPBONES_KERNEL_COLOR_RESET', "\033[0m");

abstract class Command
{
  use HasAttributes;

  /**
   * @var string
   */
  public $context;
  /**
   * @var string
   */
  public $command;
  /**
   * @var string
   */
  protected $signature;
  /**
   * @var array
   */
  protected $options = [];
  /**
   * @var string
   */
  protected $description;
  /**
   * @var array
   */
  protected $argv = [];

  /**
   *
   */
  public function __construct()
  {
    // get signature
    $parts = explode(':', $this->signature, 2);
    $this->context = $parts[0];

    // get command
    if (count($parts) > 1) {
      $parts = explode('{', $parts[1]);

      // get options
      if (count($parts) > 1) {
        $tempOptions = $parts;
        array_shift($tempOptions);
        foreach ($tempOptions as $optionInfo) {
          [$option, $description] = explode(':', $optionInfo);

          // sanitize
          $option = trim($option);
          $description = trim(rtrim($description, '}'));

          // check "=" params
          if (str_ends_with($option, '=')) {
            $option = rtrim($option, '=');
            $this->options[$option] = [
              'description' => $description,
              'param' => true,
            ];
          } else {
            $this->options[$option] = ['description' => $description];
          }
        }
      }
    }
    $this->command = trim($this->context . ':' . $parts[0]);
  }

  /**
   * Commodity to display a message in the console with color.
   *
   * @param string $str The message to display.
   * @param string $color The color to use. Default is 'white'.
   */
  protected function color($str, $color = WPBONES_KERNEL_COLOR_YELLOW)
  {
    echo $color . $str . WPBONES_KERNEL_COLOR_RESET;
  }

  /**
   * Commodity to display a message in the console.
   *
   * @param string $str The message to display.
   * @param bool $newLine Optional. Whether to add a new line at the end.
   */
  protected function info(string $str, $newLine = true)
  {
    $this->color($str, WPBONES_KERNEL_COLOR_LIGHT_MAGENTA);
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
    $this->color($str, WPBONES_KERNEL_COLOR_LIGHT_GREEN);
    echo $newLine ? "\n" : '';
  }

  /* Commodity to display an error message in the console. */
  protected function error(string $str, $newLine = true)
  {
    echo '❌ ';
    $this->color($str, WPBONES_KERNEL_COLOR_BOLD_RED);
    echo $newLine ? "\n" : '';
  }

  /* Commodity to display an info message in the console. */
  protected function warning(string $str, $newLine = true)
  {
    echo '❗ ';
    $this->color($str, WPBONES_KERNEL_COLOR_BOLD_YELLOW);
    echo $newLine ? "\n" : '';
  }

  /* Commodity to display a success message in the console. */
  protected function success($str)
  {
    $this->color('✅ ' . $str, WPBONES_KERNEL_COLOR_GREEN);
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
      WPBONES_KERNEL_COLOR_GREEN .
      "❓ $str" .
      (empty($default) ? ': ' : " (default: {$default}): ") .
      WPBONES_KERNEL_COLOR_RESET;

    // Use readline to get the user input
    $line = readline($str);

    // Trim the input to remove extra spaces or newlines
    $line = trim($line);

    return $line ?: $default;
  }

  /**
   * Return true if the options exists.
   *
   * @param string $value The option.
   *
   * @return bool|mixed
   */
  public function options(string $value)
  {
    $sanitizeOption = '--' . $value;

    if (!in_array($sanitizeOption, $this->argv)) {
      return false;
    }

    if (in_array($sanitizeOption, array_keys($this->options))) {
      $option = $this->options[$sanitizeOption];

      if (isset($option['param']) && $option['param']) {
        $argv = $this->argv;

        foreach ($argv as $argument) {
          if ($argument == $sanitizeOption) {
            $valueParam = next($argv);
            break;
          }
          next($argv);
        }

        if (empty($valueParam)) {
          $this->info('Missing param');

          return false;
        }

        return $valueParam;
      }

      return true;
    }

    return false;
  }

  /**
   * Display the help well formatted.
   *
   */
  public function displayHelp(): void
  {
    $this->info('Usage:');
    $this->line('  ' . $this->command . ' [options]');
    $this->info("\nOptions:");

    foreach ($this->options as $key => $value) {
      $column2 = $value['description'];
      if (isset($value['param'])) {
        $column1 = $key . '[=value]';
      } else {
        $column1 = $key;
      }

      $column1 = $column1 . str_repeat(' ', 22 - strlen($column1));

      $this->line("  {$column1} {$column2}");
    }
  }

  /**
   * Return the description of command console.
   *
   * @return string
   */
  public function getDescriptionAttribute(): string
  {
    return $this->description;
  }

  /**
   * Set the argv.
   *
   * @param mixed $value Usually a string.
   */
  public function setArgvAttribute($value): void
  {
    $this->argv = $value;
  }

  /**
   * Load WordPress environment.
   *
   * @return void
   */
  protected function loadWordPress()
  {
    try {
      // We have to load the WordPress environment.
      $currentDir = $_SERVER['PWD'] ?? __DIR__;
      $wpLoadPath = dirname(dirname(dirname($currentDir))) . '/wp-load.php';

      if (!file_exists($wpLoadPath)) {
        $this->wpLoaded = false;
        return;
      }

      require $wpLoadPath;
      $this->wpLoaded = true;
    } catch (\Exception $e) {
      $this->error("Error! Can't load WordPress (" . $e->getMessage() . ')');
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
    } catch (\Exception $e) {
      $this->error("Error! Can't load Composer autoload (" . $e->getMessage() . ')');
      exit();
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
    } catch (\Exception $e) {
      $this->error("Error! Can't load the plugin env (" . $e->getMessage() . ')');
      exit();
    }
  }

  /**
   * Description
   *
   * @return void
   */
  abstract public function handle();
}
