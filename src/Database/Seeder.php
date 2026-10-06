<?php

namespace WPKirk\WPBones\Database;

/**
 * Seeders are not run any more: since 3.0 seed data is a migration, which runs once per site in
 * order with the schema it needs (wpbones/WPBones#40). `php bones migrate:to-v3` turns each file
 * in database/seeders/ into one.
 *
 * The class stays so that a seeder file still loads without a fatal. That matters during the
 * update that brings a plugin onto 3.0: the 2.x code that runs it includes database/seeders/*.php,
 * and would find this class missing. Creating a seeder does nothing.
 *
 * @deprecated 3.0.0 Write a migration: \WPKirk\WPBones\Database\Migration has insert(), truncate()
 *             and isEmpty().
 */
abstract class Seeder
{
  /**
   * The table name, without the WordPress prefix.
   *
   * @var string
   */
  protected $tablename;

  /**
   * Will use the WordPress prefix of the database.
   *
   * @var bool
   */
  protected $usePrefix = true;

  /**
   * The 2.x "run only while the table is empty" flag. Ignored.
   *
   * @var bool
   */
  protected $runOnce = false;

  public function __construct()
  {
  }

  /**
   * What the seeder used to insert. Never called.
   */
  abstract public function run();

  protected function insert($sql)
  {
    return false;
  }

  protected function query($sql)
  {
    return false;
  }

  protected function truncate($tablename = '')
  {
    return false;
  }

  protected function count(): int
  {
    return 0;
  }
}
