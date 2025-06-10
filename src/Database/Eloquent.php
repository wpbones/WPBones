<?php
namespace Ondapresswp\WPBones\Database;

if (! defined('ABSPATH')) {
 exit();
}

class Eloquent
{
 public static function init($basePath)
 {
  $eloquent = '\Illuminate\Database\Capsule\Manager';

  if (class_exists($eloquent)) {
   $capsule = new $eloquent();

   $database = include_once "{$basePath}/config/database.php";
   if (is_array($database)) {

    if (is_array($database)) {
     foreach ($database as $name => $con) {
      $capsule->addConnection([
       'driver'    => $con["driver"],
       'host'      => $con["host"],
       'database'  => $con["name"],
       'username'  => $con["user"],
       'password'  => $con["password"],
       'charset'   => $con["charset"],
       'collation' => ! empty($con["collate"]) ? $con["collate"] : "utf8mb4_unicode_ci",
       'prefix'    => $con["table_prefix"],
      ], $name);
      $capsule->setAsGlobal();
      $capsule->bootEloquent();
     }
    }
   } else {

    $capsule->addConnection([
     'driver'    => 'mysql',
     'host'      => DB_HOST,
     'database'  => DB_NAME,
     'username'  => DB_USER,
     'password'  => DB_PASSWORD,
     'charset'   => 'utf8',
     'collation' => 'utf8_unicode_ci',
     'prefix'    => '',
    ]);
   }

   // Set the event dispatcher used by Eloquent models... (optional)
   // use Illuminate\Events\Dispatcher;
   // use Illuminate\Container\Container;
   // $capsule->setEventDispatcher(new Dispatcher(new Container));

   // Make this Capsule instance available globally via static methods... (optional)
   $capsule->setAsGlobal();

   // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
   $capsule->bootEloquent();
  }
 }
}
