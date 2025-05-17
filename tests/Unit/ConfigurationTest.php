<?php

declare(strict_types=1);

namespace Unit;

use Bluegents\ConventionalChangelog\Configuration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function test_constructor_with_default_values()
    {
        $config = new Configuration();

        $this->assertEquals(['feat', 'fix', 'docs', 'style', 'refactor', 'perf', 'test', 'build', 'ci', 'chore'], $config->get('types'));
        $this->assertTrue($config->get('show_breaking'));
        $this->assertEquals('CHANGELOG.md', $config->get('output_file'));
    }

    public function test_constructor_with_custom_values()
    {
        $customConfig = [
            'types' => ['feature', 'bugfix'],
            'show_breaking' => false,
            'output_file' => 'custom-changelog.md',
        ];

        $config = new Configuration($customConfig);

        $this->assertEquals(['feature', 'bugfix'], $config->get('types'));
        $this->assertFalse($config->get('show_breaking'));
        $this->assertEquals('custom-changelog.md', $config->get('output_file'));
    }

    public function test_constructor_with_partial_custom_values()
    {
        $partialConfig = [
            'types' => ['feature', 'bugfix'],
        ];

        $config = new Configuration($partialConfig);

        $this->assertEquals(['feature', 'bugfix'], $config->get('types'));
        $this->assertTrue($config->get('show_breaking'));
        $this->assertEquals('CHANGELOG.md', $config->get('output_file'));
    }

    public function test_validation_with_invalid_types()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Types must be an array');

        new Configuration(['types' => 'not-an-array']);
    }

    public function test_validation_with_invalid_show_breaking()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('show_breaking must be a boolean');

        new Configuration(['show_breaking' => 'not-a-boolean']);
    }

    public function test_get_with_valid_key()
    {
        $config = new Configuration();

        $this->assertEquals(['feat', 'fix', 'docs', 'style', 'refactor', 'perf', 'test', 'build', 'ci', 'chore'], $config->get('types'));
    }

    public function test_get_with_invalid_key()
    {
        $config = new Configuration();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Configuration key 'invalid_key' does not exist.");

        $config->get('invalid_key');
    }

    public function test_set_with_valid_key_and_value()
    {
        $config = new Configuration();

        $config->set('types', ['custom', 'types']);
        $this->assertEquals(['custom', 'types'], $config->get('types'));

        $config->set('show_breaking', false);
        $this->assertFalse($config->get('show_breaking'));

        $config->set('output_file', 'custom.md');
        $this->assertEquals('custom.md', $config->get('output_file'));
    }

    public function test_set_with_invalid_key()
    {
        $config = new Configuration();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Configuration key 'invalid_key' does not exist.");

        $config->set('invalid_key', 'value');
    }

    public function test_set_with_invalid_value()
    {
        $config = new Configuration();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Types must be an array');

        $config->set('types', 'not-an-array');
    }

    public function test_set_with_invalid_show_breaking_value()
    {
        $config = new Configuration();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('show_breaking must be a boolean');

        $config->set('show_breaking', 'not-a-boolean');
    }
}
