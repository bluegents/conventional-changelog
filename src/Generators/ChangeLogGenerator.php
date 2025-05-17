<?php

declare(strict_types=1);

namespace Bluegents\ConventionalChangelog\Generators;

use Bluegents\ConventionalChangelog\CommitParser;
use Bluegents\ConventionalChangelog\Configuration;
use Bluegents\ConventionalChangelog\Models\Release;

class ChangeLogGenerator
{
    public function __construct(
        private Configuration $configuration,
        private CommitParser $commitParser
    ) {
    }

    /**
     * Generate a changelog for a single release.
     *
     * @param array $gitCommits Array of git commits
     * @param string|null $newVersion Version number for the release
     * @return string Generated changelog in markdown format
     */
    public function generate(
        array $gitCommits,
        ?string $newVersion
    ): string {
        $commits = array_map(
            fn (array $gitCommit) => $this->commitParser->parse($gitCommit['hash'], $gitCommit['message'], $gitCommit['date']),
            $gitCommits
        );

        $release = new Release($newVersion ?: 'Unreleased', new \DateTime(), $commits);

        return $this->generateMarkdown($release);
    }

    /**
     * Generate a changelog for multiple releases.
     *
     * @param array $releases Array of releases, each with name, date, and commits
     * @return string Generated changelog in markdown format
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
            $date = $releaseData['date'] ? new \DateTime($releaseData['date']) : new \DateTime();

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
            $output .= "### {$type}\n";
            foreach ($commits as $commit) {
                $scope = $commit->getScope() ? "**{$commit->getScope()}:** " : '';
                $output .= "- {$scope}{$commit->getDescription()} (commit: {$commit->getHash()})\n";
            }
            $output .= "\n";
        }

        if ($this->configuration->get('show_breaking') && $release->hasBreakingChanges()) {
            $output .= "### Breaking Changes\n";
            foreach ($release->getBreakingChanges() as $change) {
                $output .= "- {$change->getDescription()} (commit: {$change->getHash()})\n";
            }
        }

        return $output;
    }
}
