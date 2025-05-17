<?php

declare(strict_types=1);

namespace Unit;

use Bluegents\ConventionalChangelog\CommitParser;
use Bluegents\ConventionalChangelog\Configuration;
use Bluegents\ConventionalChangelog\Generators\ChangeLogGenerator;
use Bluegents\ConventionalChangelog\Models\Commit;
use Exception;
use PHPUnit\Framework\TestCase;

class ChangeLogGeneratorTest extends TestCase
{
    private ChangeLogGenerator $changeLogGenerator;
    private Configuration $configuration;
    private CommitParser $commitParser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = $this->createMock(Configuration::class);
        $this->commitParser = $this->createMock(CommitParser::class);

        $this->changeLogGenerator = new ChangeLogGenerator(
            $this->configuration,
            $this->commitParser
        );
    }

    public function test_generate_with_single_commit()
    {
        $this->configuration->method('get')
            ->willReturnMap([
                ['types', ['feat', 'fix']],
                ['show_breaking', true],
            ]);

        $commit = new Commit(
            hash: 'abc123',
            type: 'feat',
            description: 'add new feature'
        );

        $this->commitParser->method('parse')
            ->willReturn($commit);

        $gitCommits = [
            [
                'hash' => 'abc123',
                'message' => 'feat: add new feature',
                'date' => '2023-10-01 12:00:00',
            ],
        ];

        $changelog = $this->changeLogGenerator->generate($gitCommits, '1.0.0');

        $this->assertStringContainsString('## 1.0.0', $changelog);
        $this->assertStringContainsString('### ✨ feat', $changelog);
        $this->assertStringContainsString('- add new feature (commit: abc123)', $changelog);
    }

    public function test_generate_with_multiple_commit_types()
    {
        $this->configuration->method('get')
            ->willReturnMap([
                ['types', ['feat', 'fix', 'docs']],
                ['show_breaking', true],
            ]);

        $commits = [
            new Commit(
                hash: 'abc123',
                type: 'feat',
                description: 'add new feature'
            ),
            new Commit(
                hash: 'def456',
                type: 'fix',
                description: 'fix critical bug'
            ),
            new Commit(
                hash: 'ghi789',
                type: 'docs',
                description: 'update documentation'
            ),
        ];
        $this->commitParser->method('parse')
            ->willReturnCallback(function ($hash, $message, $date) use ($commits) {
                foreach ($commits as $commit) {
                    if ($commit->getHash() === $hash) {
                        return $commit;
                    }
                }

                return null;
            });

        $gitCommits = [
            [
                'hash' => 'abc123',
                'message' => 'feat: add new feature',
                'date' => '2023-10-01 12:00:00',
            ],
            [
                'hash' => 'def456',
                'message' => 'fix: fix critical bug',
                'date' => '2023-10-01 12:00:00',
            ],
            [
                'hash' => 'ghi789',
                'message' => 'docs: update documentation',
                'date' => '2023-10-01 12:00:00',
            ],
        ];

        $changelog = $this->changeLogGenerator->generate($gitCommits, '1.0.0');

        $this->assertStringContainsString('## 1.0.0', $changelog);
        $this->assertStringContainsString('### ✨ feat', $changelog);
        $this->assertStringContainsString('- add new feature (commit: abc123)', $changelog);
        $this->assertStringContainsString('### 🐛 fix', $changelog);
        $this->assertStringContainsString('- fix critical bug (commit: def456)', $changelog);
        $this->assertStringContainsString('### 📝 docs', $changelog);
        $this->assertStringContainsString('- update documentation (commit: ghi789)', $changelog);
    }

    public function test_generate_with_breaking_changes()
    {
        $this->configuration->method('get')
            ->willReturnMap([
                ['types', ['feat', 'fix']],
                ['show_breaking', true],
            ]);

        $this->commitParser->method('parse')
            ->willReturnCallback(function ($hash, $message, $date) {
                $commits = [
                    new Commit(
                        hash: 'abc123',
                        type: 'feat',
                        description: 'add new feature',
                        isBreaking: true
                    ),
                    new Commit(
                        hash: 'def456',
                        type: 'fix',
                        description: 'fix critical bug'
                    ),
                ];
                foreach ($commits as $commit) {
                    if ($commit->getHash() === $hash) {
                        return $commit;
                    }
                }

                return null;
            });

        $gitCommits = [
            [
                'hash' => 'abc123',
                'message' => 'feat!: add new feature',
                'date' => '2023-10-01 12:00:00',
            ],
            [
                'hash' => 'def456',
                'message' => 'fix: fix critical bug',
                'date' => '2023-10-01 12:00:00',
            ],
        ];

        $changelog = $this->changeLogGenerator->generate($gitCommits, '2.0.0');

        $this->assertStringContainsString('## 2.0.0', $changelog);
        $this->assertStringContainsString('### ✨ feat', $changelog);
        $this->assertStringContainsString('- add new feature (commit: abc123)', $changelog);
        $this->assertStringContainsString('### 🐛 fix', $changelog);
        $this->assertStringContainsString('- fix critical bug (commit: def456)', $changelog);
        $this->assertStringContainsString('### 💥 Breaking Changes', $changelog);
        $this->assertStringContainsString('- add new feature (commit: abc123)', $changelog);
    }

    public function test_generate_with_scoped_commits()
    {
        $this->configuration->method('get')
            ->willReturnMap([
                ['types', ['feat', 'fix']],
                ['show_breaking', true],
            ]);

        $this->commitParser->method('parse')
            ->willReturnCallback(function ($hash, $message, $date) {
                $commits = [
                    new Commit(
                        hash: 'abc123',
                        type: 'feat',
                        description: 'add new feature',
                        scope: 'api'
                    ),
                    new Commit(
                        hash: 'def456',
                        type: 'fix',
                        description: 'fix critical bug',
                        scope: 'core'
                    ),
                ];
                foreach ($commits as $commit) {
                    if ($commit->getHash() === $hash) {
                        return $commit;
                    }
                }

                return null;
            });

        $gitCommits = [
            [
                'hash' => 'abc123',
                'message' => 'feat(api): add new feature',
                'date' => '2023-10-01 12:00:00',
            ],
            [
                'hash' => 'def456',
                'message' => 'fix(core): fix critical bug',
                'date' => '2023-10-01 12:00:00',
            ],
        ];

        $changelog = $this->changeLogGenerator->generate($gitCommits, '1.1.0');

        $this->assertStringContainsString('## 1.1.0', $changelog);
        $this->assertStringContainsString('### ✨ feat', $changelog);
        $this->assertStringContainsString('- **api:** add new feature (commit: abc123)', $changelog);
        $this->assertStringContainsString('### 🐛 fix', $changelog);
        $this->assertStringContainsString('- **core:** fix critical bug (commit: def456)', $changelog);
    }

    public function test_generate_with_filtered_commit_types()
    {
        $this->configuration->method('get')
            ->willReturnMap([
                ['types', ['feat', 'fix']],
                ['show_breaking', true],
            ]);

        $this->commitParser->method('parse')
            ->willReturnCallback(function ($hash, $message, $date) {
                $commits = [
                    new Commit(
                        hash: 'abc123',
                        type: 'feat',
                        description: 'add new feature'
                    ),
                    new Commit(
                        hash: 'def456',
                        type: 'fix',
                        description: 'fix critical bug'
                    ),
                    new Commit(
                        hash: 'ghi789',
                        type: 'chore',
                        description: 'update dependencies'
                    ),
                ];
                foreach ($commits as $commit) {
                    if ($commit->getHash() === $hash) {
                        return $commit;
                    }
                }

                return null;
            });

        $gitCommits = [
            [
                'hash' => 'abc123',
                'message' => 'feat: add new feature',
                'date' => '2023-10-01 12:00:00',
            ],
            [
                'hash' => 'def456',
                'message' => 'fix: fix critical bug',
                'date' => '2023-10-01 12:00:00',
            ],
            [
                'hash' => 'ghi789',
                'message' => 'chore: update dependencies',
                'date' => '2023-10-01 12:00:00',
            ],
        ];

        $changelog = $this->changeLogGenerator->generate($gitCommits, '1.0.1');

        $this->assertStringContainsString('## 1.0.1', $changelog);
        $this->assertStringContainsString('### ✨ feat', $changelog);
        $this->assertStringContainsString('- add new feature (commit: abc123)', $changelog);
        $this->assertStringContainsString('### 🐛 fix', $changelog);
        $this->assertStringContainsString('- fix critical bug (commit: def456)', $changelog);
        $this->assertStringNotContainsString('### 🔨 chore', $changelog);
        $this->assertStringNotContainsString('update dependencies', $changelog);
    }

    /**
     * @throws Exception
     */
    public function test_generate_multi_release()
    {
        $this->configuration->method('get')
            ->willReturnMap([
                ['types', ['feat', 'fix']],
                ['show_breaking', true],
            ]);

        $this->commitParser->method('parse')
            ->willReturnCallback(function ($hash, $message, $date) {
                $commits2 = [
                    new Commit(
                        hash: 'def456',
                        type: 'fix',
                        description: 'fix critical bug'
                    ),
                ];
                $commits1 = [
                    new Commit(
                        hash: 'abc123',
                        type: 'feat',
                        description: 'add new feature'
                    ),
                ];
                if ($hash === 'abc123') {
                    return $commits1[0];
                } elseif ($hash === 'def456') {
                    return $commits2[0];
                }

                return null;
            });

        $releases = [
            [
                'name' => 'v1.1.0',
                'date' => '2023-10-02 12:00:00',
                'commits' => [
                    [
                        'hash' => 'abc123',
                        'message' => 'feat: add new feature',
                        'date' => '2023-10-01 12:00:00',
                    ],
                ],
            ],
            [
                'name' => 'v1.0.0',
                'date' => '2023-09-01 12:00:00',
                'commits' => [
                    [
                        'hash' => 'def456',
                        'message' => 'fix: fix critical bug',
                        'date' => '2023-08-30 12:00:00',
                    ],
                ],
            ],
        ];

        $changelog = $this->changeLogGenerator->generateMultiRelease($releases);

        $this->assertStringContainsString('## v1.1.0', $changelog);
        $this->assertStringContainsString('### ✨ feat', $changelog);
        $this->assertStringContainsString('- add new feature (commit: abc123)', $changelog);

        $this->assertStringContainsString('## v1.0.0', $changelog);
        $this->assertStringContainsString('### 🐛 fix', $changelog);
        $this->assertStringContainsString('- fix critical bug (commit: def456)', $changelog);
    }
}
