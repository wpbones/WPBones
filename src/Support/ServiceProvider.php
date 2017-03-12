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
   * Dynamically handle missing method calls.
   *
   * @param  string $method
   * @param  array  $parameters
   *
   * @return mixed
   */
  public function __call( $method, $parameters )
  {
    if ( $method == 'boot' ) {
      return;
    }
  }
}