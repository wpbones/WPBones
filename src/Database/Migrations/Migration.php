<?php

namespace Ondapresswp\WPBones\Database\Migrations;

use Ondapresswp\WPBones\Database\DB;

class Migration
{
    /**
     * The charset collate of the database.
     *
     * @var string
     */
    protected $charsetCollate = 'dummy_charset_collate';

    /**
     * Will use the WordPress prefix of the database.
     *
     * @since 1.7.0
     * @var bool
     */
    protected $usePrefix = true;

    /**
     * Create a new Migration.
     */
    public function __construct()
    {
        global $wpdb;

        $this->charsetCollate = $wpdb->get_charset_collate();

        $this->up();
    }

    public function up()
    {
        // You may override this method on plugin activation
    }

    protected function create($tablename, $schema)
    {
        $table = DB::getTableName($tablename, $this->usePrefix);

        $sql = "CREATE TABLE {$table} {$schema}";
global $wpdb;
        // add ";" at the end of the string $sql if missing
        $sql = rtrim($sql, ';') . ';';
        $sql =  preg_replace('/REFERENCES\s+`?(\w+)`?/',"REFERENCES `{$wpdb->prefix}$1`" ,$sql);
#ds($sql);
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function down()
    {
        // You may override this method on plugin deactivation
    }
}
