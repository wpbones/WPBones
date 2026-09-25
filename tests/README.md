# Tests

The framework had no automated tests before this suite. The only way to exercise a
change was to edit `vendor/wpbones/wpbones/src` inside a boilerplate, click through
`wpbones.test`, and copy the result back. These tests exist so that at least the pure
PHP of the framework can be changed test-first, in this repository, in under a second.

## Running

```sh
composer install
composer test            # unit suite, must be green
composer test:defects    # tests that pin known, still-open defects — expected red
composer test:all        # both
```

## How the unit suite works without WordPress

`tests/bootstrap.php` defines the two constants the source files check (`ABSPATH`,
`ARRAY_A`), loads the package through its own Composer autoloader — so the tests run the
same files Packagist ships — and installs `Tests\Support\WpdbSpy` as the global `$wpdb`.
The spy records every SQL string it is handed and returns empty results, which lets a
test assert on the SQL the framework *builds*.

WordPress functions are called lazily by the framework, so a unit test that reaches one
fails with an "undefined function" and belongs in a WordPress-backed suite (planned:
`wp-phpunit` against a dedicated test database, with a fixture plugin owned by this
repository).

## Known-defect tests

A test in the `known-defect` group asserts the behaviour the framework *should* have and
names the issue or audit item it pins. It is excluded from `composer test` so the default
run and CI stay green, and it is run separately by `composer test:defects`, where it is
expected to fail until the defect is fixed. Fixing the defect means moving the test out
of the group, not deleting it.

## Console tests

`tests/Console` drives the real `bones` file as a process: `Support/BonesProcess.php` copies it
into a throwaway plugin under the system temp folder, runs it with `proc_open`, and kills a run
that outlives its timeout. It can run bones from the folder above the plugin
(`runFromParent()`) and put a fake `composer` first on the PATH (`withFakeComposer()`).

To show that a test fails on a previous release, point `BONES_SOURCE` at that release's tree:

```sh
mkdir -p /tmp/v2012 && git archive v2.0.12 src | tar -x -C /tmp/v2012
BONES_SOURCE=/tmp/v2012 vendor/bin/phpunit tests/Console/RenameTest.php   # expected red
```

## Layout

```
tests/
  bootstrap.php               constants, autoload, $wpdb spy
  Support/WpdbSpy.php         the $wpdb stand-in
  Support/BonesProcess.php    a throwaway plugin and bones run against it
  Unit/                       one class per framework class under test
  Console/                    the bones CLI, as a process
```

Tests are `export-ignore`d in `.gitattributes`, so they never reach a plugin's `vendor/`.
