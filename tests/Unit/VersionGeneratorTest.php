<?php

declare(strict_types=1);

namespace Unit;

use Bluegents\ConventionalChangelog\Generators\VersionGenerator;
use Bluegents\ConventionalChangelog\Models\Commit;
use PHPUnit\Framework\TestCase;

class VersionGeneratorTest extends TestCase
{
    private VersionGenerator $versionGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->versionGenerator = new VersionGenerator();
    }

    public function test_determine_next_version_with_breaking_change()
    {
        $commits = [
            $this->createCommit('feat', 'new feature', false),
            $this->createCommit('fix', 'bug fix', false),
            $this->createCommit('feat', 'breaking feature', true),
        ];

        $nextVersion = $this->versionGenerator->determineNextVersion('1.2.3', $commits);
        $this->assertEquals('2.0.0', $nextVersion);
    }

    public function test_determine_next_version_with_new_feature()
    {
        $commits = [
            $this->createCommit('feat', 'new feature', false),
            $this->createCommit('fix', 'bug fix', false),
            $this->createCommit('docs', 'update docs', false),
        ];

        $nextVersion = $this->versionGenerator->determineNextVersion('1.2.3', $commits);
        $this->assertEquals('1.3.0', $nextVersion);
    }

    public function test_determine_next_version_with_bug_fix()
    {
        $commits = [
            $this->createCommit('fix', 'bug fix', false),
            $this->createCommit('docs', 'update docs', false),
            $this->createCommit('chore', 'update dependencies', false),
        ];

        $nextVersion = $this->versionGenerator->determineNextVersion('1.2.3', $commits);
        $this->assertEquals('1.2.4', $nextVersion);
    }

    public function test_determine_next_version_with_no_relevant_commits()
    {
        $commits = [
            $this->createCommit('docs', 'update docs', false),
            $this->createCommit('chore', 'update dependencies', false),
            $this->createCommit('style', 'format code', false),
        ];

        $nextVersion = $this->versionGenerator->determineNextVersion('1.2.3', $commits);
        $this->assertEquals('1.2.3', $nextVersion);
    }

    public function test_determine_next_version_with_empty_commits()
    {
        $commits = [];

        $nextVersion = $this->versionGenerator->determineNextVersion('1.2.3', $commits);
        $this->assertEquals('1.2.3', $nextVersion);
    }

    private function createCommit(string $type, string $description, bool $isBreaking): Commit
    {
        return new Commit(
            hash: 'abc123',
            type: $type,
            description: $description,
            isBreaking: $isBreaking
        );
    }
}
