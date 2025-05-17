<?php

declare(strict_types=1);

namespace Bluegents\ConventionalChangelog\Generators;

class VersionGenerator
{
    public function determineNextVersion(
        string $currentVersion,
        array $commits
    ): string {
        $hasBreaking = false;
        $hasNewFeatures = false;
        $hasFixes = false;

        foreach ($commits as $commit) {
            if ($commit->isBreaking()) {
                $hasBreaking = true;
            } elseif ($commit->getType() === 'feat') {
                $hasNewFeatures = true;
            } elseif ($commit->getType() === 'fix') {
                $hasFixes = true;
            }
        }

        [$major, $minor, $patch] = explode('.', $currentVersion);

        if ($hasBreaking) {
            return sprintf('%d.%d.%d', intval($major) + 1, 0, 0);
        } elseif ($hasNewFeatures) {
            return sprintf('%d.%d.%d', $major, intval($minor) + 1, 0);
        } elseif ($hasFixes) {
            return sprintf('%d.%d.%d', $major, $minor, intval($patch) + 1);
        }

        return $currentVersion;
    }
}
