<?php

/**
 * The part of WordPress's WP_Error that the framework reads: code, message and data. Loaded only
 * when no WP_Error exists, by a test whose code under test builds one.
 */
class WP_Error
{
  public function __construct(public $code = '', public $message = '', public $data = '')
  {
  }

  public function get_error_code()
  {
    return $this->code;
  }

  public function get_error_message()
  {
    return $this->message;
  }

  public function get_error_data()
  {
    return $this->data;
  }
}
