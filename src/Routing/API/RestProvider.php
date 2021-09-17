<?php

namespace WPKirk\WPBones\Routing\API;

if (! defined('ABSPATH')) {
    exit;
}

use WPKirk\WPBones\Support\ServiceProvider;
use \WPKirk\WPBones\Routing\API\Route;

/**
 * This provider is used to register all API routes.
 * We are going to scan the plugin folder /api to search all vendor/version/routes.
 * 
 */
class RestProvider extends ServiceProvider
{
    protected $routes = [];

    // register
    public function register()
    {
        $api_path = $this->plugin->getBasePath() . '/api';

        $api_folder_exists = file_exists($api_path);

        if ($api_folder_exists) {
            $struct = $this->dirToArray($api_path);

            $this->routes = $this->array_flatten($struct);

            foreach ($this->routes as $vendor => $file) {
                Route::vendor($vendor);
                include $this->plugin->getBasePath() . '/api/' . $vendor . '/' . $file;
            }

            Route::register();
        }
    }

    private function dirToArray($dir)
    {
        $contents = [];

        foreach (scandir($dir) as $node) {
            if ($node == '.' || $node == '..') {
                continue;
            }
            if (is_dir($dir . '/' . $node)) {
                $contents[$node] = $this->dirToArray($dir . '/' . $node);
            } else {
                $contents = $node;
            }
        }
        return $contents;
    }

    private function array_flatten($array, $path = '')
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $new = $this->array_flatten($value, $path . $key . '/');
                $result = array_merge($result, $new);
            } else {
                $result[$path.$key] = $value;
            }
        }
        return $result;
    }
}
