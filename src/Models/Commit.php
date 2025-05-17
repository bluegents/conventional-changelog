<?php

declare(strict_types=1);

namespace Bluegents\ConventionalChangelog\Models;

use DateTimeInterface;

class Commit
{
    public function __construct(
        private string $hash,
        private string $type,
        private string $description,
        private ?string $scope = null,
        private bool $isBreaking = false,
        private ?string $body = null,
        private ?string $footer = null,
        private ?DateTimeInterface $date = null
    ) {
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function isBreaking(): bool
    {
        return $this->isBreaking;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getFooter(): ?string
    {
        return $this->footer;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }
}
