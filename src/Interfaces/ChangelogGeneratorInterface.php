<?php

declare(strict_types=1);

namespace Bluegents\ConventionalChangelog\Interfaces;

interface ChangelogGeneratorInterface
{
    public function generate(array $release): string;
}
