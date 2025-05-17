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
        // Check if the message contains literal \n\n and replace with actual newlines
        if (strpos($message, '\\n\\n') !== false) {
            $message = str_replace('\\n\\n', "\n\n", $message);
        }

        // Split the message into parts based on double newlines
        $parts = explode("\n\n", $message);
        $header = $parts[0];
        $body = $parts[1] ?? null;
        $footer = $parts[2] ?? null;

        // Parse the header
        $pattern = '/^(?<type>[a-z]+)(?:\((?<scope>[^)]+)\))?(?<breaking>!?):\s(?<description>.+)$/';

        if (! preg_match($pattern, $header, $matches)) {
            throw new InvalidArgumentException('Invalid conventional commit message.');
        }

        // Check if description is empty
        if (empty(trim($matches['description']))) {
            throw new InvalidArgumentException('Invalid conventional commit message: description cannot be empty.');
        }

        // Check if the footer contains a breaking change indicator
        $isBreaking = ! empty($matches['breaking']);

        // If there's a body but no footer, and the body contains "BREAKING CHANGE:", treat the body as the footer
        if ($body && ! $footer && strpos($body, 'BREAKING CHANGE:') !== false) {
            $footer = $body;
            $body = null;
            $isBreaking = true;
        } elseif ($footer && strpos($footer, 'BREAKING CHANGE:') !== false) {
            $isBreaking = true;
        }

        return new Commit(
            hash: $hash,
            type: $matches['type'],
            description: trim($matches['description']),
            scope: isset($matches['scope']) && ! empty($matches['scope']) ? $matches['scope'] : null,
            isBreaking: $isBreaking,
            body: $body,
            footer: $footer,
            date: new DateTime($date)
        );
    }
}
