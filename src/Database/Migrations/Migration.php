<?php

namespace WPKirk\WPBones\Database\Migrations;

/**
 * The 2.x name of the migration base class, kept so that migration files written for 2.x keep
 * loading. It is the 3.0 class: creating it runs nothing, the Migrator calls up().
 *
 * The new name matters for the update that brings a plugin onto 3.0, because that update is run
 * by the 2.x code still in memory, whose upgrader hook includes every migration file. A file that
 * extends \WPKirk\WPBones\Database\Migration names a class the 2.x code never had, so it is loaded
 * from the new files and does nothing; this name could already be loaded with its 2.x constructor,
 * which runs up(). `php bones migrate:to-v3` rewrites the `use` line.
 *
 * @deprecated 3.0.0 Extend \WPKirk\WPBones\Database\Migration.
 */
abstract class Migration extends \WPKirk\WPBones\Database\Migration
{
}
