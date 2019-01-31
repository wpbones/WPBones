<?php

namespace WPKirk\WPBones\Foundation\Console;

class Kernel
{

  protected $commands = [
  ];

  private $instances = [];

  protected function line( $str )
  {
    echo "\033[38;5;82m" . $str;
    echo "\033[0m\n";
  }

  public function __construct()
  {
    foreach ( $this->commands as $commandClass ) {
      $instance                                                    = new $commandClass;
      $this->instances[ $instance->context ][ $instance->command ] = $instance;
    }
  }

  public function hasCommands()
  {
    return ! empty( $this->instances );
  }

  public function displayHelp()
  {
    foreach ( $this->instances as $context => $commands ) {
      if ( count( $commands ) > 1 ) {
        $this->line( ' ' . $context );
      }

      foreach ( $commands as $key => $command ) {
        $name = $command->command;
        $name .= str_repeat( ' ', 23 - strlen( $name ) );
        $description = $command->description;
        $this->line( " {$name} {$description}" );
      }
    }
  }

  public function handle( $argv )
  {
    // wpkirk:sample
    $commandConsole = $argv[ 0 ];

    foreach ( $this->instances as $commands ) {

      if ( in_array( $commandConsole, array_keys( $commands ) ) ) {
        array_shift( $argv );
        if ( in_array( '--help', $argv ) ) {
          $commands[ $commandConsole ]->displayHelp();

          return true;
        }
        $commands[ $commandConsole ]->argv = $argv;
        $commands[ $commandConsole ]->handle();

        return true;
      }
    }
  }
}