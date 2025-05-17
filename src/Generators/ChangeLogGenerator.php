<?php

declare(strict_types=1);

namespace Bluegents\ConventionalChangelog\Generators;

use Bluegents\ConventionalChangelog\CommitParser;
use Bluegents\ConventionalChangelog\Configuration;
use Bluegents\ConventionalChangelog\Models\Release;
use DateTime;

class ChangeLogGenerator
{
    private array $typeIcons = [
        'feat' => '✨',
        'fix' => '🐛',
        'docs' => '📝',
        'style' => '💄',
        'refactor' => '♻️',
        'perf' => '⚡️',
        'test' => '✅',
        'build' => '🔧',
        'ci' => '👷',
        'chore' => '🔨',
    ];

    public function __construct(
        private Configuration $configuration,
        private CommitParser $commitParser
    ) {
    }

    public function generate(
        array $gitCommits,
        ?string $newVersion
    ): string {
        $commits = array_map(
            fn (array $gitCommit) => $this->commitParser->parse($gitCommit['hash'], $gitCommit['message'], $gitCommit['date']),
            $gitCommits
        );

        $release = new Release($newVersion ?: 'Unreleased', new DateTime(), $commits);

        return $this->generateMarkdown($release);
    }

    /**
     * @throws \Exception
     */
    public function generateMultiRelease(array $releases): string
    {
        $output = '';

        foreach ($releases as $releaseData) {
            $commits = array_map(
                fn (array $gitCommit) => $this->commitParser->parse($gitCommit['hash'], $gitCommit['message'], $gitCommit['date']),
                $releaseData['commits']
            );

            $version = $releaseData['name'] === 'HEAD' ? 'Unreleased' : $releaseData['name'];
            $date = $releaseData['date'] ? new DateTime($releaseData['date']) : new DateTime();

            $release = new Release($version, $date, $commits);

            $output .= $this->generateMarkdown($release) . "\n";
        }

        return $output;
    }

    private function generateMarkdown(Release $release): string
    {
        $output = "## {$release->getVersion()} - {$release->getDate()->format('Y-m-d')}\n\n";

        $grouped = [];
        foreach ($release->getCommits() as $commit) {
            if (! in_array($commit->getType(), $this->configuration->get('types'))) {
                continue;
            }
            $grouped[$commit->getType()][] = $commit;
        }

        foreach ($grouped as $type => $commits) {
            $icon = $this->typeIcons[$type] ?? '';
            $output .= "### {$icon} {$type}\n";

            $uniqueCommits = [];
            foreach ($commits as $commit) {
                $key = $commit->getScope() . '|' . $commit->getDescription();
                if (! isset($uniqueCommits[$key])) {
                    $uniqueCommits[$key] = $commit;
                }
            }

            foreach ($uniqueCommits as $commit) {
                $scope = $commit->getScope() ? "**{$commit->getScope()}:** " : '';
                $output .= "- {$scope}{$commit->getDescription()} (commit: {$commit->getHash()})\n";
            }
            $output .= "\n";
        }

        if ($this->configuration->get('show_breaking') && $release->hasBreakingChanges()) {
            $output .= "### 💥 Breaking Changes\n";
            foreach ($release->getBreakingChanges() as $change) {
                $output .= "- {$change->getDescription()} (commit: {$change->getHash()})\n";
            }
        }

        return $output;
    }
}
