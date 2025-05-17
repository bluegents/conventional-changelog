<?php

declare(strict_types=1);

namespace Bluegents\ConventionalChangelog;

use Bluegents\ConventionalChangelog\Models\Commit;
use DateTime;
use Exception;
use InvalidArgumentException;

class CommitParser
{
    /**
     * @throws Exception
     */
    public function parse(
        string $hash,
        string $message,
        string $date
    ): Commit {
        if (str_contains($message, '\\n\\n')) {
            $message = str_replace('\\n\\n', "\n\n", $message);
        }

        $parts = explode("\n\n", $message);
        $header = $parts[0];
        $body = $parts[1] ?? null;
        $footer = $parts[2] ?? null;
        $pattern = '/^(?<type>[a-z]+)(?:\((?<scope>[^)]+)\))?(?<breaking>!?):\s(?<description>.+)$/';

        if (! preg_match($pattern, $header, $matches)) {
            throw new InvalidArgumentException('Invalid conventional commit message.');
        }

        if (empty(trim($matches['description']))) {
            throw new InvalidArgumentException('Invalid conventional commit message: description cannot be empty.');
        }

        $isBreaking = ! empty($matches['breaking']);
        if ($body && ! $footer && str_contains($body, 'BREAKING CHANGE:')) {
            $footer = $body;
            $body = null;
            $isBreaking = true;
        } elseif ($footer && str_contains($footer, 'BREAKING CHANGE:')) {
            $isBreaking = true;
        }

        return new Commit(
            hash: $hash,
            type: $matches['type'],
            description: trim($matches['description']),
            scope: ! empty($matches['scope']) ? $matches['scope'] : null,
            isBreaking: $isBreaking,
            body: $body,
            footer: $footer,
            date: new DateTime($date)
        );
    }
}
