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

        return self::mysql(DB_HOST);
    }

    /**
     * A MySQL connection that talks to the database the way WordPress does.
     *
     * The host goes through $wpdb->parse_db_host(), as in wpdb::db_connect(): DB_HOST may carry a
     * port (`localhost:3307`), a socket (`localhost:/tmp/mysql.sock`) or an IPv6 address. The
     * charset and collation are the ones WordPress settled on ($wpdb->charset, $wpdb->collate),
     * which it raises to utf8mb4 even when DB_CHARSET says utf8: the tables are utf8mb4, and a utf8
     * connection can neither write an emoji into them nor read one back.
     *
     * @param string $dbHost The DB_HOST setting.
     *
     * @return array
     */
    protected static function mysql(string $dbHost): array
    {
        global $wpdb;

        $connection = [
            'driver' => 'mysql',
            'host' => $dbHost,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => is_object($wpdb) && !empty($wpdb->charset) ? $wpdb->charset : 'utf8mb4',
            'prefix' => '',
        ];

        // No collation means the charset's default one; an empty one would be sent as `collate ''`.
        if (is_object($wpdb) && !empty($wpdb->collate)) {
            $connection['collation'] = $wpdb->collate;
        }

        $parsed = is_object($wpdb) && method_exists($wpdb, 'parse_db_host') ? $wpdb->parse_db_host($dbHost) : false;

        if (is_array($parsed)) {
            [$host, $port, $socket, $isIpv6] = $parsed;

            $connection['host'] = $isIpv6 && extension_loaded('mysqlnd') ? "[$host]" : $host;

            if ($port) {
                $connection['port'] = $port;
            }

            if ($socket) {
                $connection['unix_socket'] = $socket;
            }
        }

        return $connection;
    }
}
