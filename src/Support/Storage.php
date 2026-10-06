<?php

namespace WPKirk\WPBones\Support;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Where a plugin keeps the files it generates: compiled views, logs.
 *
 * Up to 2.x they went inside the plugin folder (0777 up to 2.1.2), where the web server serves
 * them as text, and where a read-only plugin folder breaks Blade. Since 3.0 they go under
 * the uploads directory, which WordPress keeps writable, in wpbones/<plugin>/<folder>: an
 * index.php in every folder, a deny-all .htaccess at the top for the servers that read it.
 *
 * @since 3.0.0
 */
class Storage
{
  /**
   * The folder, created if needed.
   *
   * @param string $plugin The plugin's folder name (basename of its base path): known when the
   *                       plugin boots, unlike its slug, which is read from the header on init.
   * @param string $folder What goes in it: `views`, `logs`.
   *
   * @return string The absolute path, without a trailing slash.
   */
  public static function path(string $plugin, string $folder): string
  {
    // Whatever it is given, it never names a folder outside wpbones/.
    $plugin = preg_replace('/[^A-Za-z0-9_-]/', '', $plugin) ?: 'plugin';
    $folder = preg_replace('/[^A-Za-z0-9_-]/', '', $folder) ?: 'files';

    $uploads = wp_upload_dir(null, false);
    $base = empty($uploads['error']) && !empty($uploads['basedir']) ? $uploads['basedir'] : '';

    $path = self::create($base, $plugin, $folder);

    // No uploads directory, or one that cannot be written: the system's temporary folder.
    return $path ?? (self::create(get_temp_dir(), $plugin, $folder) ?? untrailingslashit(get_temp_dir()));
  }

  /**
   * Create base/wpbones/plugin/folder with its index files; null when it cannot be written.
   */
  private static function create(string $base, string $plugin, string $folder): ?string
  {
    if ($base === '') {
      return null;
    }

    $root = trailingslashit($base) . 'wpbones';
    $path = "{$root}/{$plugin}/{$folder}";

    if (!is_dir($path) && !wp_mkdir_p($path)) {
      return null;
    }

    if (!is_writable($path)) {
      return null;
    }

    foreach ([$root, "{$root}/{$plugin}", $path] as $level) {
      if (!file_exists("{$level}/index.php")) {
        @file_put_contents("{$level}/index.php", "<?php\n// Silence is golden.\n");
      }
    }

    // Left alone once it exists: the site's owner may have written their own.
    if (!file_exists("{$root}/.htaccess")) {
      @file_put_contents(
        "{$root}/.htaccess",
        "# Generated files of WP Bones plugins: not for the web.\n" .
          "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n" .
          "<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n"
      );
    }

    return $path;
  }
}
