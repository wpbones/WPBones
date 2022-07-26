<?php

namespace WPKirk\WPBones\Database\Migrations;

use WPKirk\WPBones\Database\DB;

class Migration
{
  protected $charsetCollate = 'dummy_charset_collate';

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
    $table = DB::getTableName($tablename);

    $sql = "CREATE TABLE {$table} {$schema};";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }

  public function down()
  {
    // You may override this method on plugin deactivation
  }
}
