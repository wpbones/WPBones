<?php

namespace WPKirk\WPBones\Routing\API;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
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
   * @var \WP_REST_Request
   */
  protected WP_REST_Request $request;

  public function __construct($request, $vendor)
  {
    $this->request = $request;
    $this->vendor  = $vendor;
  }

  /**
   * Commodity method for the response.
   */
  public function response($data, $status = 200): WP_REST_Response
  {
    return new WP_REST_Response($data, $status);
  }

  /**
   * Commodity method for an error response.
   *
   * @param string $code
   * @param string $message
   * @param int    $status
   *
   * @return \WP_Error
   *
   *
   */
  public function responseError(string $code, string $message, int $status = 400): WP_Error
  {
    return new WP_Error($code, $message, ['status' => $status]);
  }
}
