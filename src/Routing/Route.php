<?php

namespace WPKirk\WPBones\Routing;

if (! defined('ABSPATH')) {
    exit;
}

class Route
{

    /**
     * Custom page routes list.
     *
     * @var array
     */
    public static $menu = [];

    /**
     * Set the right route path.
     *
     * @param string $path
     */
    public static function get(string $path)
    {
        self::$menu[] = $path;
    }
}
