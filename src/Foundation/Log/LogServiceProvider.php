<?php

namespace WPKirk\WPBones\Foundation\Log;

use WPKirk\WPBones\Support\ServiceProvider;

if (! defined('ABSPATH')) {
    exit;
}

class LogServiceProvider extends ServiceProvider
{

    /**
     * Log filename.
     *
     * @var string
     */
    protected $filename;

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
    protected $log;

    /**
     * What we can loggin.
     *
     * @var string
     */
    protected $logLevel;

    /**
     * The Log levels.
     *
     * @var array
     */
    protected $levels = [
        'debug'     => 'DEBUG',
        'info'      => 'INFO',
        'notice'    => 'NOTICE',
        'warning'   => 'WARNING',
        'error'     => 'ERROR',
        'critical'  => 'CRITICAL',
        'alert'     => 'ALERT',
        'emergency' => 'EMERGENCY',
    ];

    /**
     * The Log levels colors.
     *
     * @var array
     */
    protected $colors = [
        'debug'     => "\e[38;5;7m",
        'info'      => "\e[38;5;4m",
        'notice'    => "\e[38;5;3m",
        'warning'   => "\e[38;5;211m",
        'error'     => "\e[38;5;1m",
        'critical'  => "\e[38;5;1m",
        'alert'     => "\e[38;5;1m",
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

        // first of all check if log is enabled
        $this->log = $plugin->config('plugin.log');

        if (in_array($this->log, [false, 'false', 'FALSE', 'none', 'N', 'n', 'off', 'OFF'])) {
            $this->log = false;

            return;
        }

        // the plugin path
        $pluginPath = $plugin->getBasePath();

        // default log storage folder
        $logFolder = "{$pluginPath}/storage/logs/";

        // create if it doesn't exists
        if (! file_exists($logFolder)) {
            mkdir($logFolder, 0777, true);
        }

        // get the minimun log level
        $this->logLevel = $plugin->config('plugin.log_level');

        // get the right filename
        $this->filename = ($this->log == 'single') ? 'debug.log' : date('Y-m-d') . '.log';

        // complete log path
        $this->path = "{$logFolder}{$this->filename}";
    }


    public function register()
    {
        return $this;
    }

    /**
     * Dynamically handle missing method calls.
     * We are overridding the parent method.
     *
     * @param  string $method
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function __call(string $method, $parameters)
    {
        if ($method == 'boot') {
            return;
        }

        if (in_array($method, array_keys($this->levels))) {
            $level = strtolower($this->levels[ $method ]);
            $args  = array_merge([$level], $parameters);

            return call_user_func_array([$this, 'write'], $args);
        }
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
        // if log is disabled, nothing to do
        if (! $this->log) {
            return;
        }

        // get the color console
        $color = $this->colors[ $level ];

        // get the debug level
        $l = $this->levels[ $level ];

        // sanitize the context
        $c = empty($context) ? '' : json_encode($context, JSON_PRETTY_PRINT);

        // sanitize the message
        if (! is_string($message)) {
            $message = json_encode($message, JSON_PRETTY_PRINT);
        }

        // log in default WordPress
        $eStr = "{$color}[{$l}]: {$message} {$c}\e[0m";
        error_log($eStr);

        if ($this->log !== 'errorlog') {
            error_log($eStr . PHP_EOL, 3, $this->path);
        }
    }
}
