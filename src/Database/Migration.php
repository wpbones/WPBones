<?php

namespace WPKirk\WPBones\Database;

/**
 * A migration: one change to the database, run once per site.
 *
 * A file in `database/migrations/` returns an instance:
 *
 *     return new class extends Migration {
 *       public function up()
 *       {
 *         $this->create('products', "(id bigint(20) unsigned NOT NULL auto_increment, ...) {$this->charsetCollate};");
 *       }
 *     };
 *
 * The Migrator includes the file and calls up() itself, once, and records the file name. Since
 * 3.0 creating the object runs nothing: up() used to be called by the constructor, so including a
 * file was the same as running it, every time.
 *
 * Seed data is a migration too: insert() and isEmpty() are here for that.
 *
 * @since 3.0.0
 */
abstract class Migration
{
  /**
   * The charset and collation of the database, ready to append to a CREATE TABLE.
   *
   * @var string
   */
  protected $charsetCollate = '';

  /**
   * Will use the WordPress prefix of the database.
   *
   * @var bool
   */
  protected $usePrefix = true;

  public function __construct()
  {
    global $wpdb;

    $this->charsetCollate = $wpdb->get_charset_collate();
  }

  /**
   * Apply the change.
   */
  abstract public function up();

  /**
   * Kept for the files written before 3.0, which may define it. Nothing calls it: a deactivation
   * must not drop data, and removing a plugin's tables belongs in its uninstall.php.
   */
  public function down()
  {
  }

  /**
   * Create a table, or bring an existing one to this schema, through dbDelta().
   *
   * @param string $tablename The table name, without the WordPress prefix.
   * @param string $schema    The column and key definitions, in parentheses.
   */
  protected function create($tablename, $schema)
  {
    // Validated before anything reaches dbDelta(): the name is interpolated into SQL.
    $table = $this->table($tablename);

    $sql = "CREATE TABLE {$table} {$schema}";

    // add ";" at the end of the string $sql if missing
    $sql = rtrim($sql, ';') . ';';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
  }

  /**
   * Insert rows: `$this->insert('products', "(name, price) VALUES ('iMac', 100000)")`.
   *
   * @param string $tablename The table name, without the WordPress prefix.
   * @param string $sql       Everything after `INSERT INTO table`.
   *
   * @return int|bool The number of rows inserted, false on error.
   */
  protected function insert($tablename, $sql)
  {
    global $wpdb;

    return $wpdb->query("INSERT INTO `{$this->table($tablename)}` {$sql}");
  }

  /**
   * Empty a table.
   *
   * @param string $tablename The table name, without the WordPress prefix.
   *
   * @return int|bool
   */
  protected function truncate($tablename)
  {
    global $wpdb;

    return $wpdb->query("TRUNCATE TABLE `{$this->table($tablename)}`");
  }

  /**
   * Whether a table has no rows: what a seed that must not repeat itself checks first.
   *
   * @param string $tablename The table name, without the WordPress prefix.
   */
  protected function isEmpty($tablename): bool
  {
    global $wpdb;

    return (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$this->table($tablename)}`") === 0;
  }

  /**
   * The full table name, prefixed unless $usePrefix is false, and refused unless it is a plain
   * identifier.
   *
   * @param string $tablename The table name, without the WordPress prefix.
   */
  protected function table($tablename): string
  {
    return DB::assertTableName(DB::getTableName($tablename, $this->usePrefix));
  }
}
