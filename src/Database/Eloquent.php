<?php

namespace WPKirk\WPBones\Database;

if (!defined('ABSPATH')) {
    exit();
}

class Eloquent
{
    public static function init()
    {
        $eloquent = '\Illuminate\Database\Capsule\Manager';

        if (class_exists($eloquent)) {
            $capsule = new $eloquent();

            $capsule->addConnection(self::connection());

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

    /**
     * The connection to WordPress's own database: MySQL, unless WordPress runs on SQLite.
     *
     * That is WordPress Playground, WordPress Studio, or any site with the SQLite Database
     * Integration plugin, which defines DB_ENGINE and FQDB. Its tables are plain SQLite tables
     * under their MySQL names, in the file FQDB points at, so Eloquent can query them directly.
     *
     * @return array
     */
    public static function connection(): array
    {
        if (defined('DB_ENGINE') && DB_ENGINE === 'sqlite' && defined('FQDB')) {
            return [
                'driver' => 'sqlite',
                'database' => FQDB,
                'prefix' => '',
            ];
        }

        return [
            'driver' => 'mysql',
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ];
    }
}
