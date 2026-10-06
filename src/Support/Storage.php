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
 * There is no fallback: when uploads cannot be written, or the deny rule cannot be put in place,
 * path() answers null and the caller does without (a shared temporary folder would let other
 * local users read the logs and plant compiled views, Codex on #128).
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
   * @return string|null The absolute path, without a trailing slash; null when it cannot be had.
   */
  public static function path(string $plugin, string $folder): ?string
  {
    // Whatever it is given, it never names a folder outside wpbones/.
    $plugin = preg_replace('/[^A-Za-z0-9_-]/', '', $plugin) ?: 'plugin';
    $folder = preg_replace('/[^A-Za-z0-9_-]/', '', $folder) ?: 'files';

    $uploads = wp_upload_dir(null, false);
    $base = empty($uploads['error']) && !empty($uploads['basedir']) ? $uploads['basedir'] : '';

    return self::create($base, $plugin, $folder);
  }

  /**
   * Create base/wpbones/plugin/folder with its index files and the deny rule; null when the folder
   * cannot be written or the rule is not there.
   */
  private static function create(string $base, string $plugin, string $folder): ?string
  {
    if ($base === '') {
      return null;
    }

    $root = trailingslashit($base) . 'wpbones';
    $path = "{$root}/{$plugin}/{$folder}";

    // wp_mkdir_p() answers false when another request made the folder first: it is there all the same.
    if (!is_dir($path) && !wp_mkdir_p($path) && !is_dir($path)) {
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

    // Left alone once it exists, as the site's owner may have written their own, but only if it
    // denies: without a rule, Apache would serve what this folder holds, so then there is no folder.
    // A short write (a full disk) is removed rather than left to pass for a rule (Codex on #128).
    $htaccess = "{$root}/.htaccess";

    if (!file_exists($htaccess)) {
      $rule = "# Generated files of WP Bones plugins: not for the web.\n" .
        "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n" .
        "<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n";

      // Written aside and renamed into place, so that a request never reads it half written.
      $draft = $htaccess . '.' . uniqid('', true);

      if (@file_put_contents($draft, $rule) !== strlen($rule) || !@rename($draft, $htaccess)) {
        @unlink($draft);

        if (!file_exists($htaccess)) {
          return null;
        }
      }
    }

    // `Deny from all` (Apache 2.2) or `Require all denied` (2.4): either is a rule.
    if (!preg_match('/\bden(?:y|ied)\b/i', (string) @file_get_contents($htaccess))) {
      return null;
    }

    return $path;
  }

  /**
   * Remove a plugin's folder, for its uninstall.php: `Storage::delete(basename(__DIR__))`.
   *
   * @param string $plugin The plugin's folder name, as given to path().
   */
  public static function delete(string $plugin): void
  {
    $plugin = preg_replace('/[^A-Za-z0-9_-]/', '', $plugin);
    $uploads = wp_upload_dir(null, false);

    if ($plugin === '' || !empty($uploads['error']) || empty($uploads['basedir'])) {
      return;
    }

    $dir = trailingslashit($uploads['basedir']) . "wpbones/{$plugin}";

    if (!is_dir($dir) || is_link($dir)) {
      return;
    }

    $entries = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($entries as $entry) {
      $entry->isDir() && !$entry->isLink() ? @rmdir($entry->getPathname()) : @unlink($entry->getPathname());
    }

    @rmdir($dir);
  }
}
