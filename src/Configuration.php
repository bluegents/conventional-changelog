<?php

declare(strict_types=1);

namespace Bluegents\ConventionalChangelog;

use InvalidArgumentException;

class Configuration
{
    private array $config;

    public function __construct(
        array $config = []
    ) {
        $this->config = array_merge($this->getDefaults(), $config);
        $this->validate();
    }

    private function getDefaults(): array
    {
        return [
            'types' => ['feat', 'fix', 'docs', 'style', 'refactor', 'perf', 'test', 'build', 'ci', 'chore'],
            'show_breaking' => true,
            'output_file' => 'CHANGELOG.md',
        ];
    }

    private function validate(): void
    {
        if (! is_array($this->config['types'])) {
            throw new InvalidArgumentException('Types must be an array');
        }

        if (! is_bool($this->config['show_breaking'])) {
            throw new InvalidArgumentException('show_breaking must be a boolean');
        }
    }

    public function get(string $key): mixed
    {
        if (! array_key_exists($key, $this->config)) {
            throw new InvalidArgumentException("Configuration key '{$key}' does not exist.");
        }

        return $this->config[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        if (! array_key_exists($key, $this->config)) {
            throw new InvalidArgumentException("Configuration key '{$key}' does not exist.");
        }

        $this->config[$key] = $value;
        $this->validate();
    }
}
