<?php

namespace Ondapresswp\WPBones\Foundation\Log;

use Ondapresswp\WPBones\Support\ServiceProvider;

if (!defined('ABSPATH')) {
  exit();
}

class LogServiceProvider extends ServiceProvider
{
  /**
   * Log filename.
   *
   * @var string
   */
  private $filename;

  /**
   * Log complete path.
   *
   * @var string
   */
  protected $path;

  /**
   * Available Settings: "single", "daily", "errorlog".
   * When set to "errorlog", we'll use only error_log() WordPress function.
   *
   * @var string
   */
  private $log;

  /**
   * The log path.
   *
   * @var string
   */
  protected $logPath;

  /**
   * The log daily format.
   *
   * @var string
   */
  protected $dailyFormat = 'Y-m-d';

  /**
   * The Log levels.
   * The levels are used to determine the importance of log messages.
   * They will be use as dynamic methods to log messages.
   * The levels are: debug, info, notice, warning, error, critical, alert, emergency.
   *
   * @example
   * $this->debug('This is a debug message');
   * $this->info('This is an info message');
   *
   * @var array
   */
  protected $levels = [
    'debug' => 'DEBUG',
    'info' => 'INFO',
    'notice' => 'NOTICE',
    'warning' => 'WARNING',
    'error' => 'ERROR',
    'critical' => 'CRITICAL',
    'alert' => 'ALERT',
    'emergency' => 'EMERGENCY',
  ];

  /**
   * The Log levels colors.
   *
   * @var array
   */
  protected $colors = [
    'debug' => "\e[38;5;7m",
    'info' => "\e[38;5;4m",
    'notice' => "\e[38;5;3m",
    'warning' => "\e[38;5;211m",
    'error' => "\e[38;5;1m",
    'critical' => "\e[38;5;1m",
    'alert' => "\e[38;5;1m",
    'emergency' => "\e[38;5;200m",
  ];

  /**
   * LogServiceProvider constructor.
   *
   * @param $plugin
   */
  public function __construct($plugin)
  {
    parent::__construct($plugin);

    // first check if log storage is enabled
    $this->log = $plugin->config('plugin.logging.type', 'errorlog');
    $this->logPath = $plugin->config('plugin.logging.path', "{$plugin->basePath}/storage/logs/");
    $this->dailyFormat = $plugin->config('plugin.logging.daily_format', 'Y-m-d');

    // Check if the date format is prefixed with a string
    if (strpos($this->dailyFormat, '|') !== false) {
      list($prefix, $date_format) = explode('|', $this->dailyFormat);
    } else {
      $prefix = '';
      $date_format = $this->dailyFormat;
    }
    $this->dailyFormat = $prefix . date($date_format);

    // Backward compatibility
    // if it's not set, check the old config and set the default value
    if ($this->log === null) {
      $this->log = $plugin->config('plugin.log', 'errorlog');
    }

    if (in_array($this->log, [false, 'false', 'FALSE', 'none', 'N', 'n', 'off', 'OFF'], true)) {
      $this->log = false;

      return;
    }

    // create if it doesn't exist
    if (!file_exists($this->logPath)) {
      mkdir($this->logPath, 0777, true);
    }

    // get the right filename
    $this->filename = $this->log == 'single' ? 'debug.log' : $this->dailyFormat . '.log';

    // complete log path
    $this->path = "{$this->logPath}{$this->filename}";
  }

  /**
   * Write the log.
   *
   * @param string $level
   * @param mixed  $message
   * @param array  $context
   */
  protected function write($level = 'debug', $message = '', $context = [])
  {
    // get the color console
    $color = $this->colors[$level];

    // get the debug level
    $l = $this->levels[$level];

    // sanitize the context
    $c = empty($context) ? '' : json_encode($context, JSON_PRETTY_PRINT);

    // sanitize the message
    if (!is_string($message)) {
      $message = json_encode($message, JSON_PRETTY_PRINT);
    }

    // log in default WordPress
    $eStr = "{$color}[{$l}]: {$message} {$c}\e[0m";
    error_log($eStr);

    if ($this->log !== false) {
      $eStrWithDate = '[' . date('d-M-Y H:i:s T') . '] ' . $eStr;
      error_log($eStrWithDate . PHP_EOL, 3, $this->path);
    }
  }

  /**
   * Register the service provider.
   *
   * @access private
   *
   * @return $this
   */
  public function register()
  {
    return $this;
  }

  /**
   * Dynamically handle missing method calls.
   * We are overriding the parent method.
   *
   * @param string $method
   * @param array  $parameters
   *
   * @return mixed|null
   */
  public function __call(string $method, $parameters)
  {
    if ($method == 'boot') {
      return;
    }

    if (in_array($method, array_keys($this->levels))) {
      $level = strtolower($this->levels[$method]);
      $args = array_merge([$level], $parameters);

      return call_user_func_array([$this, 'write'], $args);
    }
  }
}
