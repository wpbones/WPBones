<?php

/**
 * Unit-suite bootstrap: no WordPress.
 *
 * Every file under src/ only needs these to be *loaded*:
 *  - the ABSPATH guard at the top of most classes,
 *  - the ARRAY_A constant the QueryBuilder passes to $wpdb,
 *  - a global $wpdb object.
 *
 * WordPress functions are called lazily by the framework, so a test that reaches one
 * fails with a clear "undefined function" and belongs in the (future) WordPress-backed
 * suite, not here.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
  define('ABSPATH', __DIR__ . '/');
}

if (!defined('ARRAY_A')) {
  define('ARRAY_A', 'ARRAY_A');
}

require dirname(__DIR__) . '/vendor/autoload.php';

$GLOBALS['wpdb'] = new WPKirk\WPBones\Tests\Support\WpdbSpy();
