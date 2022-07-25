<?php

namespace WPKirk\WPBones\Database\Migrations;

use WPKirk\WPBones\Database\DB;

class Migration
{
  protected $charsetCollate = 'dummy_charset_collate';

  /**
   * The database table name with WordPress prefix.
   * Usually, you should use the model name as the table name.
   * It will be converted to lowercase and with the WordPress prefix.
   *
   * @var string
   */
  protected $tablename = 'dummy_table_name';

  /**
   * Create a new Migration.
   */
  public function __construct()
  {
    global $wpdb;

    $this->charsetCollate = $wpdb->get_charset_collate();
    $this->tablename      = DB::getTableName($this->tablename);

    error_log($this->tablename);

    $this->up();
  }

  // You may override this method on plugin deactivation

  public function up()
  {
    // You may override this method on plugin activation
  }

  // You may override this method on plugin deactivation

  protected function tablename()
  {
  }

  protected function create($sql)
  {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }

  public function down()
  {
    // You may override this method on plugin deactivation
  }
}
