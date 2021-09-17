<?php

namespace WPKirk\WPBones\Routing\API;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * You'll extend you REST API Controller from this class.
 *
 */
abstract class RestController
{

    /**
     * The vendor name and path
     *
     * @var string
     */
    protected $vendor;

    /**
     * WP_REST_Request object
     *
     * @var WP_REST_Request
     */
    protected $request;

    public function __construct($request, $vendor)
    {
        $this->request = $request;
        $this->vendor = $vendor;
    }

    /**
     * Commodity method for the response.
     */
    public function response($data, $status = 200)
    {
        return new \WP_REST_Response($data, $status);
    }

    /**
     * Commodity method for an error response.
     */
    public function responseError($code, $message, $status = 400)
    {
        return new \WP_Error($code, $message, ['status' => $status ]);
    }
}
