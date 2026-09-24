<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use WPKirk\WPBones\Tests\Support\BonesProcess;

/**
 * The `make:*` generators up to 2.0.9 (bones CLI audit, 2026-09-24):
 *
 * - overwrote an existing file without asking and said "Created";
 * - took the class name as a path: `../../Escaped` left the target folder, `my-model` wrote a
 *   file that does not parse;
 * - `make:provider Folder/Class` wrote nothing, because it kept `Folder/Class` as the class
 *   name, and still said "Created", like every generator whose write failed;
 * - `make:eloquent-model` always wrote `namespace …\Models\;`, which does not parse;
 * - `migrate:create` with no name was a TypeError.
 *
 * Every generated file must now exist, parse, and declare the namespace its folder implies.
 */
#[Group('console')]
final class MakeCommandsTest extends TestCase
{
  private BonesProcess $bones;

  protected function setUp(): void
  {
    parent::setUp();

    $this->bones = (new BonesProcess())->withStubs();
  }

  protected function tearDown(): void
  {
    $this->bones->remove();

    parent::tearDown();
  }

  private function assertParses(string $file): void
  {
    $output = [];
    $status = 0;
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $status);

    $this->assertSame(0, $status, "{$file} does not parse:\n" . implode("\n", $output));
  }

  /**
   * @return array<string, array{0:string[], 1:string, 2:string, 3:string}>
   */
  public static function generators(): array
  {
    return [
      'controller in a folder' => [['make:controller', 'Shop/Cart'], '', 'plugin/Http/Controllers/Shop/Cart.php', 'WPKirk\Http\Controllers\Shop'],
      'model in a folder' => [['make:model', 'Shop/Item'], '', 'plugin/Models/Shop/Item.php', 'WPKirk\Models\Shop'],
      'eloquent model' => [['make:eloquent-model', 'Book'], '', 'plugin/Models/Book.php', 'WPKirk\Models'],
      'eloquent model in a folder' => [['make:eloquent-model', 'Shop/Book'], '', 'plugin/Models/Shop/Book.php', 'WPKirk\Models\Shop'],
      'api controller in a folder' => [['make:api', 'Shop/Orders'], '', 'plugin/API/Shop/Orders.php', 'WPKirk\API\Shop'],
      'provider' => [['make:provider', 'Billing'], '', 'plugin/Providers/Billing.php', 'WPKirk\Providers'],
      'provider in a folder' => [['make:provider', 'Shop/Billing'], '', 'plugin/Providers/Shop/Billing.php', 'WPKirk\Providers\Shop'],
      'ajax in a folder' => [['make:ajax', 'Shop/Search'], '', 'plugin/Ajax/Shop/Search.php', 'WPKirk\Ajax\Shop'],
      'schedule' => [['make:schedule', 'Nightly'], '', 'plugin/Providers/Nightly.php', 'WPKirk\Providers'],
      'shortcode in a folder' => [['make:shortcode', 'Shop/Badge'], '', 'plugin/Shortcodes/Shop/Badge.php', 'WPKirk\Shortcodes\Shop'],
      'widget in a folder' => [['make:widget', 'Shop/Latest'], '', 'plugin/Widgets/Shop/Latest.php', 'WPKirk\Widgets\Shop'],
      'custom post type' => [['make:cpt', 'Book'], "book\nBook\nBooks\n", 'plugin/CustomPostTypes/Book.php', 'WPKirk\CustomPostTypes'],
      'custom taxonomy' => [['make:ctt', 'Genre'], "genre\nGenre\nGenres\nbook\n", 'plugin/CustomTaxonomyTypes/Genre.php', 'WPKirk\CustomTaxonomyTypes'],
      'console command' => [['make:console', 'Hello'], "\n\n", 'plugin/Console/Commands/Hello.php', 'WPKirk\Console\Commands'],
    ];
  }

  /**
   * @param string[] $arguments
   */
  #[DataProvider('generators')]
  public function test_every_generator_writes_a_file_that_parses(array $arguments, string $stdin, string $file, string $namespace): void
  {
    $run = $this->bones->run($arguments, $stdin);
    $path = $this->bones->plugin . '/' . $file;

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertFileExists($path, $run['output']);
    $this->assertParses($path);

    $content = (string) file_get_contents($path);
    $class = basename($file, '.php');

    $this->assertStringContainsString("namespace {$namespace};", $content);
    $this->assertMatchesRegularExpression("/^\\s*(final\\s+)?class\\s+{$class}\\b/m", $content);
    $this->assertStringContainsString("Created {$file}", $run['stdout']);
  }

  public function test_an_existing_file_is_not_overwritten(): void
  {
    mkdir($this->bones->plugin . '/plugin/Http/Controllers', 0777, true);
    file_put_contents($this->bones->plugin . '/plugin/Http/Controllers/Probe.php', '<?php // my work');

    $run = $this->bones->run(['make:controller', 'Probe']);

    $this->assertNotSame(0, $run['status'], $run['output']);
    $this->assertSame('<?php // my work', file_get_contents($this->bones->plugin . '/plugin/Http/Controllers/Probe.php'));
    $this->assertStringContainsString('--force', $run['stderr']);
    $this->assertStringNotContainsString('Created', $run['stdout']);
  }

  public function test_force_overwrites_an_existing_file_wherever_it_is_written(): void
  {
    mkdir($this->bones->plugin . '/plugin/Http/Controllers', 0777, true);
    file_put_contents($this->bones->plugin . '/plugin/Http/Controllers/Probe.php', '<?php // my work');

    $run = $this->bones->run(['make:controller', '--force', 'Probe']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertStringContainsString('class Probe', (string) file_get_contents($this->bones->plugin . '/plugin/Http/Controllers/Probe.php'));
  }

  public function test_a_name_that_climbs_out_of_the_folder_is_refused(): void
  {
    $run = $this->bones->run(['make:controller', '../../Escaped']);

    $this->assertNotSame(0, $run['status'], $run['output']);
    $this->assertFileDoesNotExist($this->bones->plugin . '/plugin/Escaped.php');
    $this->assertFileDoesNotExist($this->bones->plugin . '/Escaped.php');
  }

  public function test_a_name_that_is_not_a_php_class_is_refused(): void
  {
    $run = $this->bones->run(['make:model', 'my-model']);

    $this->assertNotSame(0, $run['status'], $run['output']);
    $this->assertSame([], glob($this->bones->plugin . '/plugin/Models/*') ?: []);
  }

  public function test_a_console_command_cannot_go_in_a_folder_the_kernel_does_not_read(): void
  {
    $run = $this->bones->run(['make:console', 'Shop/Hello'], "\n\n");

    $this->assertNotSame(0, $run['status'], $run['output']);
    $this->assertFileDoesNotExist($this->bones->plugin . '/plugin/Console/Commands/Shop/Hello.php');
  }

  public function test_a_second_widget_keeps_the_views_the_first_one_wrote(): void
  {
    $this->assertSame(0, $this->bones->run(['make:widget', 'First'])['status']);

    $form = $this->bones->plugin . '/resources/views/widgets/wp-kirk-form.php';
    $this->assertFileExists($form);
    file_put_contents($form, '<h2>edited</h2>');

    $run = $this->bones->run(['make:widget', 'Second']);

    $this->assertSame(0, $run['status'], $run['output']);
    $this->assertSame('<h2>edited</h2>', file_get_contents($form));
  }

  public function test_migrate_create_without_a_name_fails_cleanly(): void
  {
    $run = $this->bones->run(['migrate:create']);

    $this->assertNotSame(0, $run['status'], $run['output']);
    $this->assertStringNotContainsString('TypeError', $run['output']);
  }

  public function test_migrate_create_writes_its_folder_and_a_file_that_parses(): void
  {
    $run = $this->bones->run(['migrate:create', 'books']);

    $this->assertSame(0, $run['status'], $run['output']);

    $files = glob($this->bones->plugin . '/database/migrations/*_create_books_table.php') ?: [];
    $this->assertCount(1, $files, $run['output']);
    $this->assertParses($files[0]);
  }
}
