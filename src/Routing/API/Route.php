<?php

namespace WPKirk\WPBones\Routing\API;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * You'll use this class to create a route.
 */
class Route
{
    protected static $vendor;
    protected static $apis = [];

    // available rest verb/methods from wp-includes/rest-api/class-wp-rest-server.php
    const METHODS = ['get', 'post', 'put', 'patch', 'delete'];

    public function __construct($vendor)
    {
        self::$vendor = $vendor;
    }

    public static function vendor($vendor)
    {
        self::$vendor = $vendor;
    }

    /**
     * Internal used to build the callback when we're using "@" syntax.
     * Or a callable function.
     */
    private static function callback($callback, $vendor)
    {
        if (is_callable($callback)) {
            return $callback;
        }

        if (is_string($callback) && strpos($callback, '@') !== false) {

            /**
             * In args you'll find the WP_REST_Request object.
             *
             *  // You can access parameters via direct array access on the object:
             *  $param = $request['some_param'];
             *
             *  // Or via the helper method:
             *  $param = $request->get_param( 'some_param' );
             *
             *  // You can get the combined, merged set of parameters:
             *  $parameters = $request->get_params();
             *
             *  // The individual sets of parameters are also available, if needed:
             *  $parameters = $request->get_url_params();
             *  $parameters = $request->get_query_params();
             *  $parameters = $request->get_body_params();
             *  $parameters = $request->get_json_params();
             *  $parameters = $request->get_default_params();
             *
             *  // Uploads aren't merged in, but can be accessed separately:
             *  $parameters = $request->get_file_params();
             */

            return function ($args = null) use ($callback, $vendor) {
                list($controller, $method) = explode('@', $callback);
                $instance  = new $controller($args, $vendor);
                return $instance->{$method}($args);
            };
        }
    }

    /**
     * Internal register the route.
     */
    public static function register()
    {
        add_action('rest_api_init', function () {
            foreach (self::$apis as $vendor => $api) {
                foreach ($api as $method => $route) {
                    foreach ($route as $route_name => $route_args) {
                        register_rest_route($vendor, $route_name, [
                        'methods' => strtoupper($method),
                        'callback' => self::callback($route_args['callback'], $vendor),
                     ]);
                    }
                }
            }
        });
    }

    /**
     * Magic method to register the route. I mean, a single verb/route.
     */
    public static function __callStatic($method, $args)
    {
        $method = strtolower($method);

        if (in_array($method, self::METHODS)) {
            @[$path, $callback, $options] = $args;

            self::$apis[self::$vendor][$method][$path] = [
                'callback' => $callback,
                'options' => $options
            ];
        }
    }

    /**
     * You may use this method to register multiple routes at once.
     *
     * @example
     *
     *  Route::register(['get', 'post'], '/', function () {});
     *
     */
    public static function request($methods, $path, $callback, $options = [])
    {
        $methods = (array) $methods;

        foreach ($methods as $method) {
            $method = strtolower($method);

            self::$apis[self::$vendor][$method][$path] = [
                'callback' => $callback,
                'options' => $options
            ];
        }
    }

    /**
     * Commodity method for the response.
     */
    public static function response($data, $status = 200)
    {
        return new \WP_REST_Response($data, $status);
    }

    /**
     * Commodity method for an error response.
     */
    public static function responseError($code, $message, $status = 400)
    {
        return new \WP_Error($code, $message, ['status' => $status ]);
    }
}
