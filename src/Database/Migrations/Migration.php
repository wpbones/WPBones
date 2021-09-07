<?php

namespace WPKirk\WPBones\Database\Migrations;

use WPKirk\WPBones\Support\Str;

class Migration
{
    protected $charsetCollate = 'dummy_charset_collate';
    protected $tablename      = 'dummy_table_name';

    /**
     * Create a new Migration.
     */
    public function __construct()
    {
        global $wpdb;

        $this->charsetCollate = $wpdb->get_charset_collate();
        $this->tablename      = $wpdb->prefix . Str::snake(Str::studly(get_called_class()));

        $this->up();
    }

    // You may override this method on plugin deactivation
    public function up()
    {
        // You may override this method on plugin activation
    }

    // You may override this method on plugin deactivation

    protected function create($sql)
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    protected function tablename()
    {
    }

    public function down()
    {
        // You may override this method on plugin deactivation
    }
}
