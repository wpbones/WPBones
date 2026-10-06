<?php

namespace WPKirk\WPBones\Foundation\Log;

use WPKirk\WPBones\Support\ServiceProvider;
use WPKirk\WPBones\Support\Storage;

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
    $this->logPath = $plugin->config('plugin.logging.path');
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

    // "errorlog" means error_log() only, as documented; up to 2.1.2 it also wrote a daily file.
    if ($this->log === 'errorlog' || in_array($this->log, [false, 'false', 'FALSE', 'none', 'N', 'n', 'off', 'OFF'], true)) {
      $this->log = false;

      return;
    }

    // get the right filename
    $this->filename = $this->log == 'single' ? 'debug' : $this->dailyFormat;

    if (empty($this->logPath)) {
      // Since 3.0 the default folder is under uploads, not <plugin>/storage/logs, where the web
      // server served the files; the name carries a hash keyed with the site's AUTH_SALT, so that
      // it cannot be guessed where the folder's .htaccess is not read. Not wp_hash(): it is
      // pluggable, and the plugin boots before pluggable.php is loaded.
      // The plugin's folder name, not its slug: the slug is read from the header on init, after
      // this provider is made.
      $folder = basename($plugin->basePath);
      $logs = Storage::path($folder, 'logs');

      // No folder to write to: error_log() only, as "errorlog".
      if ($logs === null) {
        $this->log = false;

        return;
      }

      $this->logPath = trailingslashit($logs);
      // AUTH_SALT only: ABSPATH differs between a web request and WP-CLI, and between the
      // releases of an atomic deploy. The salt WordPress keeps in the options when wp-config.php
      // defines none.
      $key = defined('AUTH_SALT') && AUTH_SALT ? AUTH_SALT : (string) get_site_option('auth_salt', '');
      $this->filename .= '-' . substr(hash_hmac('sha256', 'wpbones-log-' . $folder, $key), 0, 12);
    } else {
      $this->logPath = trailingslashit($this->logPath);

      if (!file_exists($this->logPath)) {
        wp_mkdir_p($this->logPath);
      }
    }

    $this->filename .= '.log';

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
