<?php

namespace Ondapresswp\WPBones\Foundation\Console;

class Kernel
{
  protected $commands = [];

  private $instances = [];

  public function __construct()
  {
    foreach ($this->commands as $commandClass) {
      $instance = new $commandClass();
      $this->instances[$instance->context][$instance->command] = $instance;
    }
  }

  public function hasCommands(): bool
  {
    return !empty($this->instances);
  }

  public function handle($argv)
  {
    // wpkirk:sample
    $commandConsole = $argv[0];

    foreach ($this->instances as $commands) {
      if (in_array($commandConsole, array_keys($commands))) {
        array_shift($argv);
        if (in_array('--help', $argv)) {
          $commands[$commandConsole]->displayHelp();

          return true;
        }
        $commands[$commandConsole]->argv = $argv;
        $commands[$commandConsole]->handle();

        return true;
      }
    }
  }

  public function displayHelp()
  {
    foreach ($this->instances as $context => $commands) {
      if (count($commands) > 1) {
        $this->line(' ' . $context);
      }

      foreach ($commands as $key => $command) {
        $name = $command->command;
        $times = 23 - strlen($name);
        if($times>0)
        $name .= str_repeat(' ', $times);
        $description = $command->description;
        $this->line(" {$name} {$description}");
      }
    }
  }

  protected function line($str)
  {
    echo "\033[38;5;82m" . $str;
    echo "\033[0m\n";
  }
}
