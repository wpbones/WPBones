<?php

namespace WPKirk\WPBones\Console;

use WPKirk\WPBones\Support\Traits\HasAttributes;

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
    $parts         = explode(":", $this->signature, 2);
    $this->context = $parts[0];

    // get command
    if (count($parts) > 1) {
      $parts = explode('{', $parts[1]);

      // get options
      if (count($parts) > 1) {
        $tempOptions = $parts;
        array_shift($tempOptions);
        foreach ($tempOptions as $optionInfo) {
          [$option, $description] = explode(":", $optionInfo);

          // sanitize
          $option      = trim($option);
          $description = trim(rtrim($description, "}"));

          // check "=" params
          if (str_ends_with($option, "=")) {
            $option                 = rtrim($option, "=");
            $this->options[$option] = ['description' => $description, 'param' => true];
          } else {
            $this->options[$option] = ['description' => $description];
          }
        }
      }
    }
    $this->command = trim(($this->context . ":" . $parts[0]));
  }

  /**
   * Make a request to end user in the console and return the user input or the default value.
   *
   * @param string $str     The question string.
   * @param string $default Optional. A default value whether the user press return.
   *
   * @return string
   */
  protected function ask(string $str, string $default = ''): string
  {
    echo "\n\e[38;5;88m$str" . (empty($default) ? "" : " (default: {$default})") . "\e[0m ";

    $handle = fopen("php://stdin", "r");
    $line   = fgets($handle);

    fclose($handle);

    return trim($line, " \n\r");
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
   * Display a colored line in the console.
   *
   * @param $str String to display.
   */
  protected function info(string $str): void
  {
    echo "\033[38;5;213m" . $str;
    echo "\033[0m\n";
  }

  /**
   * Display the help well formatted.
   *
   */
  public function displayHelp(): void
  {
    $this->info("Usage:");
    $this->line("  " . $this->command . " [options]");
    $this->info("\nOptions:");

    foreach ($this->options as $key => $value) {
      $column2 = $value['description'];
      if (isset($value['param'])) {
        $column1 = $key . "[=value]";
      } else {
        $column1 = $key;
      }

      $column1 = $column1 . str_repeat(" ", (22 - strlen($column1)));

      $this->line("  {$column1} {$column2}");
    }
  }

  /**
   * Display a colored line in the console.
   *
   * @param $str String to display.
   */
  protected function line(string $str): void
  {
    echo "\033[38;5;82m" . $str;
    echo "\033[0m\n";
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
   * Description
   *
   * @return void
   */
  abstract public function handle();
}
