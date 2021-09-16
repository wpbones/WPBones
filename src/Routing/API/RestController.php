<?php

namespace WPKirk\WPBones\Routing\API;

if (! defined('ABSPATH')) {
    exit;
}

abstract class RestController
{
    public function response($data, $status = 200)
    {
        return new \WP_REST_Response($data, $status);
    }

    public function responseError($code, $message, $status = 400)
    {
        return new \WP_Error($code, $message, ['status' => $status ]);
    }
}
