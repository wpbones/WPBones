<?php

namespace WPKirk\WPBones\Console;

use WPKirk\WPBones\Support\Str;

abstract class Command
{

  protected $signature;

  protected $options = [];

  protected $description;

  protected $argv = [];

  public $context;

  public $command;

  /**
   * Display a colored line in the console.
   *
   * @param $str String to display.
   */
  protected function line( $str )
  {
    echo "\033[38;5;82m" . $str;
    echo "\033[0m\n";
  }

  /**
   * Display a colored line in the console.
   *
   * @param $str String to display.
   */
  protected function info( $str )
  {
    echo "\033[38;5;213m" . $str;
    echo "\033[0m\n";
  }

  /**
   * Make a request to end user in the console and return the user input or the default value.
   *
   * @param string $str     The question string.
   * @param string $default Optional. A default value whether the user press return.
   *
   * @return string
   */
  protected function ask( $str, $default = '' )
  {

    echo "\n\e[38;5;88m$str" . ( empty( $default ) ? "" : " (default: {$default})" ) . "\e[0m ";

    $handle = fopen( "php://stdin", "r" );
    $line   = fgets( $handle );

    fclose( $handle );

    return trim( $line, " \n\r" );
  }

  public function __construct()
  {
    // get signature
    $parts         = explode( ":", $this->signature, 2 );
    $this->context = $parts[ 0 ];

    // get command
    if ( count( $parts ) > 1 ) {
      $parts = explode( '{', $parts[ 1 ] );

      // get options
      if ( count( $parts ) > 1 ) {
        $tempOptions = $parts;
        array_shift( $tempOptions );
        foreach ( $tempOptions as $optionInfo ) {
          list( $option, $description ) = explode( ":", $optionInfo );

          // sanitize
          $option      = trim( $option );
          $description = trim( rtrim( $description, "}" ) );

          // check "=" params
          if ( "=" == substr( $option, -1 ) ) {
            $option                   = rtrim( $option, "=" );
            $this->options[ $option ] = [ 'description' => $description, 'param' => true ];
          }
          else {
            $this->options[ $option ] = [ 'description' => $description ];
          }
        }
      }
    }
    $this->command = trim( ( $this->context . ":" . $parts[ 0 ] ) );
  }

  /**
   * Return true if the options exists.
   *
   * @param string $value The option.
   *
   * @return bool|mixed|void
   */
  public function options( $value )
  {
    $sanitizeOption = '--' . $value;

    if ( ! in_array( $sanitizeOption, $this->argv ) ) {
      return false;
    }

    if ( in_array( $sanitizeOption, array_keys( $this->options ) ) ) {
      $option = $this->options[ $sanitizeOption ];

      if ( isset( $option[ 'param' ] ) && $option[ 'param' ] ) {

        $argv = $this->argv;

        foreach ( $argv as $argument ) {
          if ( $argument == $sanitizeOption ) {
            $valueParam = next( $argv );
            break;
          }
          next( $argv );
        }

        if ( ! isset( $valueParam ) || empty( $valueParam ) ) {
          return $this->info( 'Missing param' );
        }

        return $valueParam;
      }

      return true;
    }

    return false;
  }

  /**
   * Display the help well formatted.
   *
   */
  public function displayHelp()
  {
    $this->info( "Usage:" );
    $this->line( "  " . $this->command . " [options]" );
    $this->info( "\nOptions:" );

    foreach ( $this->options as $key => $value ) {
      $column2 = $value[ 'description' ];
      if ( isset( $value[ 'param' ] ) ) {
        $column1 = $key . "[=value]";
      }
      else {
        $column1 = $key;
      }

      $column1 = $column1 . str_repeat( " ", ( 22 - strlen( $column1 ) ) );

      $this->line( "  {$column1} {$column2}" );
    }
  }

  /**
   * Return the description of command console.
   *
   * @return string
   */
  public function getDescriptionAttribute()
  {
    return $this->description;
  }

  /**
   * Set the argv.
   *
   * @param mixed $value Usually a string.
   */
  public function setArgvAttribute( $value )
  {
    $this->argv = $value;
  }

  /**
   * Return the value of the method `get{Name}Attribute`.
   *
   * @param string $name Usually the protected property name.
   *
   * @return mixed
   */
  public function __get( $name )
  {
    $method = 'get' . Str::studly( $name ) . 'Attribute';
    if ( method_exists( $this, $method ) ) {
      return $this->{$method}();
    }
  }

  /**
   * Set the value of the method `set{Name}Attribute`.
   *
   * @param string $name Usually the protected property name.
   * @param mixed  $value
   *
   * @return mixed
   */
  public function __set( $name, $value )
  {
    $method = 'set' . Str::studly( $name ) . 'Attribute';
    if ( method_exists( $this, $method ) ) {
      return $this->{$method}( $value );
    }
  }

  abstract public function handle();
}