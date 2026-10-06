<?php

namespace WPKirk\WPBones\Database;

use RuntimeException;

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
 * Seed data is a migration too: insert(), truncate(), count(), isEmpty() and query() are here for
 * that, as they were on the 2.x Seeder, with the table named in each call.
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

  /**
   * The database errors dbDelta() left behind while create() still got the table and every
   * column it declares: see create().
   *
   * @var array<int, array{query: string, error_str: string}>
   */
  protected array $toleratedErrors = [];

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
   * Put each column on its own line: dbDelta() splits the schema on new lines.
   *
   * It fails, by throwing, when the table or one of the columns it declares is not there
   * afterwards. Anything else dbDelta() could not apply to an existing table, say a default it
   * tries to change and MySQL refuses, is logged and tolerated: until 2.x every migration ran
   * again on each activation and update, and those errors went by unseen, so the migrations that
   * plugins already ship can produce them when 3.0 applies them once more.
   *
   * @param string $tablename The table name, without the WordPress prefix.
   * @param string $schema    The column and key definitions, in parentheses.
   *
   * @throws RuntimeException When the table, or a column it declares, is missing afterwards.
   */
  protected function create($tablename, $schema)
  {
    global $wpdb;

    // Validated before anything reaches dbDelta(): the name is interpolated into SQL.
    $table = $this->table($tablename);

    $sql = "CREATE TABLE {$table} {$schema}";

    // add ";" at the end of the string $sql if missing
    $sql = rtrim($sql, ';') . ';';

    if (!function_exists('dbDelta')) {
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    $before = count($GLOBALS['EZSQL_ERROR'] ?? []);

    dbDelta($sql);

    $errors = array_slice($GLOBALS['EZSQL_ERROR'] ?? [], $before);
    $cause = $errors === [] ? '' : ': ' . end($errors)['error_str'];

    $suppress = $wpdb->suppress_errors();
    // DESCRIBE, as dbDelta() itself uses: the SQLite integration of WordPress Playground knows it.
    $columns = array_map('strtolower', (array) $wpdb->get_col("DESCRIBE `{$table}`", 0));
    $wpdb->suppress_errors($suppress);

    if ($columns === []) {
      throw new RuntimeException("dbDelta() did not create {$table}{$cause}");
    }

    $missing = array_diff(static::declaredColumns($schema), $columns);

    if ($missing !== []) {
      throw new RuntimeException(sprintf('dbDelta() did not add %s to %s%s', implode(', ', $missing), $table, $cause));
    }

    foreach ($errors as $error) {
      $this->toleratedErrors[] = $error;

      // The probe dbDelta() makes of a table before creating it always fails: not news.
      if (stripos(ltrim((string) ($error['query'] ?? '')), 'DESCRIBE ') !== 0) {
        error_log(sprintf('[WP Bones] dbDelta() left %s as it was for: %s (%s)', $table, $error['query'] ?? '', $error['error_str'] ?? ''));
      }
    }
  }

  /**
   * The database errors create() tolerated, which the Migrator does not count as a failure.
   *
   * @internal
   *
   * @return array<int, array{query: string, error_str: string}>
   */
  public function toleratedErrors(): array
  {
    return $this->toleratedErrors;
  }

  /**
   * The column names a schema declares, lowercase, read the way dbDelta() reads them: one per
   * line, the first word, keys and constraints left out.
   *
   * @return string[]
   */
  protected static function declaredColumns(string $schema): array
  {
    if (!preg_match('|\((.*)\)|ms', $schema, $match)) {
      return [];
    }

    $columns = [];

    foreach (explode("\n", trim($match[1])) as $line) {
      $line = trim($line, " \t\n\r\0\x0B,");

      preg_match('|^([^ ]*)|', $line, $first);
      $name = strtolower(trim($first[1] ?? '', '`'));

      if (!in_array($name, ['', 'primary', 'index', 'fulltext', 'unique', 'key', 'spatial', 'constraint', 'foreign', 'check'], true)) {
        $columns[] = $name;
      }
    }

    return $columns;
  }

  /**
   * Insert rows: `$this->insert('products', "(name, price) VALUES ('iMac', 100000)")`.
   *
   * @param string $tablename The table name, without the WordPress prefix.
   * @param string $sql       Everything after `INSERT INTO table`.
   *
   * @return int The number of rows inserted.
   *
   * @throws RuntimeException When the database refuses it.
   */
  protected function insert($tablename, $sql)
  {
    return $this->query("INSERT INTO `{$this->table($tablename)}` {$sql}");
  }

  /**
   * Empty a table.
   *
   * @param string $tablename The table name, without the WordPress prefix.
   *
   * @return int
   *
   * @throws RuntimeException When the database refuses it.
   */
  protected function truncate($tablename)
  {
    return $this->query("TRUNCATE TABLE `{$this->table($tablename)}`");
  }

  /**
   * The number of rows in a table.
   *
   * @param string $tablename The table name, without the WordPress prefix.
   */
  protected function count($tablename): int
  {
    global $wpdb;

    $count = $wpdb->get_var("SELECT COUNT(*) FROM `{$this->table($tablename)}`");

    if ($count === null) {
      throw new RuntimeException("Could not count the rows of {$this->table($tablename)}: " . ($wpdb->last_error ?? ''));
    }

    return (int) $count;
  }

  /**
   * Whether a table has no rows: what a seed that must not repeat itself checks first.
   *
   * @param string $tablename The table name, without the WordPress prefix.
   */
  protected function isEmpty($tablename): bool
  {
    return $this->count($tablename) === 0;
  }

  /**
   * Run any SQL statement.
   *
   * It throws when wpdb answers false, which it also does without recording an error: a value too
   * long for its column, or one the charset cannot hold. The Migrator would not see those.
   *
   * @param string $sql
   *
   * @return int|bool What wpdb::query() answers: rows affected, or true for a statement that
   *                  affects none.
   *
   * @throws RuntimeException When the database refuses it.
   */
  protected function query($sql)
  {
    global $wpdb;

    $result = $wpdb->query($sql);

    if ($result === false) {
      throw new RuntimeException('The database refused ' . strtok(trim($sql), " \n") . ': ' . (($wpdb->last_error ?? '') ?: 'no reason given'));
    }

    return $result;
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
