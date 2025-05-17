<?php

declare(strict_types=1);

namespace Bluegents\ConventionalChangelog\Generators;

use Bluegents\ConventionalChangelog\Interfaces\ChangelogGeneratorInterface;

class MarkdownGenerator implements ChangelogGeneratorInterface
{
    public function generate(array $release): string
    {
        return '# Changelog';
    }
}
