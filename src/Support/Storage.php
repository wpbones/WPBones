<?php

namespace WPKirk\WPBones\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The folders a plugin writes its generated files to: compiled views, logs.
 *
 * @since 2.1.3
 */
class Storage
{
  /**
   * Make the folder, with WordPress's permissions, and close it to the web as far as a file
   * can say so: an index.php, and a deny-all .htaccess for the servers that read one. A file
   * that exists already is left as it is.
   *
   * @param string $dir An absolute path.
   *
   * @return bool Whether the folder exists.
   */
  public static function prepare(string $dir): bool
  {
    if (!is_dir($dir) && !wp_mkdir_p($dir)) {
      return false;
    }

    if (!file_exists("{$dir}/index.php")) {
      @file_put_contents("{$dir}/index.php", "<?php\n// Silence is golden.\n");
    }

    if (!file_exists("{$dir}/.htaccess")) {
      @file_put_contents(
        "{$dir}/.htaccess",
        "# Generated files of a WP Bones plugin: not for the web.\n" .
          "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n" .
          "<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n"
      );
    }

    return true;
  }
}
