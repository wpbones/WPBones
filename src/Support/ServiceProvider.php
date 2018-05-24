<?php

namespace WPKirk\WPBones\Support;

abstract class ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    abstract public function register();

    /**
     * Instance of main plugin.
     *
     * @var
     */
    protected $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Dynamically handle missing method calls.
     *
     * @param  string $method
     * @param  array  $parameters
     */
    public function __call(string $method, $parameters)
    {
        if ($method == 'boot') {
            return;
        }
    }
}