<?php

declare(strict_types=1);

namespace WPKirk\WPBones\Tests\Console;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Issue #103: the CLI guard still said PHP 7.4 two releases after composer.json moved
 * to `>=8.1`, so a PHP 7.4–8.0 shell got past it and into code that needs 8.1.
 *
 * The two numbers live in different files and nothing tied them together.
 */
#[Group('console')]
final class PhpRequirementTest extends TestCase
{
  public function test_the_cli_guard_matches_the_composer_requirement(): void
  {
    $root = dirname(__DIR__, 2);

    $composer = json_decode((string) file_get_contents($root . '/composer.json'), true);
    $this->assertMatchesRegularExpression('/^>=\d+\.\d+$/', $composer['require']['php']);
    $required = substr($composer['require']['php'], 2);

    $bones = (string) file_get_contents($root . '/src/Console/bin/bones');
    $this->assertSame(1, preg_match("/define\\('WPBONES_MINIMAL_PHP_VERSION', '([^']+)'\\)/", $bones, $match));

    $this->assertSame($required, $match[1], 'WPBONES_MINIMAL_PHP_VERSION disagrees with composer.json');
  }
}
