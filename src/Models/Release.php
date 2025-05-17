<?php

declare(strict_types=1);

namespace Bluegents\ConventionalChangelog\Models;

class Release
{
    public function __construct(
        private string $version,
        private \DateTimeInterface $date,
        private array $commits = []
    ) {
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function getCommits(): array
    {
        return $this->commits;
    }

    public function hasBreakingChanges(): bool
    {
        foreach ($this->commits as $commit) {
            if ($commit->isBreaking()) {
                return true;
            }
        }

        return false;
    }

    public function getBreakingChanges(): array
    {
        return array_filter($this->commits, fn ($commit) => $commit->isBreaking());
    }
}
