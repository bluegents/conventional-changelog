<?php

declare(strict_types=1);

namespace Unit;

use Bluegents\ConventionalChangelog\Services\GitService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class GitServiceTest extends TestCase
{
    private readonly string $repoPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoPath = sys_get_temp_dir() . '/git-repo';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        exec("rm -rf {$this->repoPath}");
    }

    public function test_get_commits_without_from_parameter()
    {
        $processMock = $this->createMock(Process::class);

        $processMock->method('isSuccessful')->willReturn(true);
        $processMock->method('getOutput')->willReturn(
            "abc123|feat: new feature|2023-01-01 10:00:00\n" .
            'def456|fix: bug fix|2023-01-02 11:00:00'
        );

        $gitService = $this->getMockBuilder(GitService::class)
            ->setConstructorArgs([$this->repoPath])
            ->onlyMethods(['createProcess'])
            ->getMock();

        $gitService->method('createProcess')->willReturn($processMock);

        $commits = $gitService->getCommits();
        $this->assertCount(2, $commits);

        $this->assertEquals('abc123', $commits[0]['hash']);
        $this->assertEquals('feat: new feature', $commits[0]['message']);
        $this->assertEquals('2023-01-01 10:00:00', $commits[0]['date']);

        $this->assertEquals('def456', $commits[1]['hash']);
        $this->assertEquals('fix: bug fix', $commits[1]['message']);
        $this->assertEquals('2023-01-02 11:00:00', $commits[1]['date']);
    }

    public function test_get_commits_with_from_parameter()
    {
        $processMock = $this->createMock(Process::class);

        $processMock->method('isSuccessful')->willReturn(true);
        $processMock->method('getOutput')->willReturn(
            'ghi789|docs: update readme|2023-01-03 12:00:00'
        );

        $gitService = $this->getMockBuilder(GitService::class)
            ->setConstructorArgs([$this->repoPath])
            ->onlyMethods(['createProcess'])
            ->getMock();

        $gitService->method('createProcess')->willReturn($processMock);

        $commits = $gitService->getCommits('v1.0.0', 'v2.0.0');
        $this->assertCount(1, $commits);
        $this->assertEquals('ghi789', $commits[0]['hash']);
        $this->assertEquals('docs: update readme', $commits[0]['message']);
        $this->assertEquals('2023-01-03 12:00:00', $commits[0]['date']);
    }

    public function test_get_commits_with_failed_process()
    {
        $processMock = $this->createMock(Process::class);

        $processMock->method('isSuccessful')->willReturn(false);
        $processMock->method('getErrorOutput')->willReturn('Command failed: fatal: not a git repository');

        $gitService = $this->getMockBuilder(GitService::class)
            ->setConstructorArgs([$this->repoPath])
            ->onlyMethods(['createProcess'])
            ->getMock();

        $gitService->method('createProcess')->willReturn($processMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Git command failed: Command failed: fatal: not a git repository');
        $gitService->getCommits();
    }

    public function test_get_commits_with_empty_output()
    {
        $processMock = $this->createMock(Process::class);

        $processMock->method('isSuccessful')->willReturn(true);
        $processMock->method('getOutput')->willReturn('');

        $gitService = $this->getMockBuilder(GitService::class)
            ->setConstructorArgs([$this->repoPath])
            ->onlyMethods(['createProcess'])
            ->getMock();

        $gitService->method('createProcess')->willReturn($processMock);

        $commits = $gitService->getCommits();
        $this->assertIsArray($commits);
        $this->assertEmpty($commits);
    }
    public function test_get_tags()
    {
        $processMock = $this->createMock(Process::class);

        $processMock->method('isSuccessful')->willReturn(true);
        $processMock->method('getOutput')->willReturn(
            "v1.1.0|2023-10-02 12:00:00|abc123\n" .
            'v1.0.0|2023-09-01 12:00:00|def456'
        );

        $gitService = $this->getMockBuilder(GitService::class)
            ->setConstructorArgs([$this->repoPath])
            ->onlyMethods(['createTagsProcess'])
            ->getMock();

        $gitService->expects($this->once())
            ->method('createTagsProcess')
            ->willReturn($processMock);

        $tags = $gitService->getTags();
        $this->assertCount(2, $tags);

        $this->assertEquals('v1.1.0', $tags[0]['name']);
        $this->assertEquals('2023-10-02 12:00:00', $tags[0]['date']);
        $this->assertEquals('abc123', $tags[0]['hash']);

        $this->assertEquals('v1.0.0', $tags[1]['name']);
        $this->assertEquals('2023-09-01 12:00:00', $tags[1]['date']);
        $this->assertEquals('def456', $tags[1]['hash']);
    }

    public function test_get_commits_by_release()
    {
        $gitService = $this->getMockBuilder(GitService::class)
            ->setConstructorArgs([$this->repoPath])
            ->onlyMethods(['getTags', 'getCommits'])
            ->getMock();

        $gitService->method('getTags')->willReturn([
            [
                'name' => 'v1.1.0',
                'date' => '2023-10-02 12:00:00',
                'hash' => 'abc123',
            ],
            [
                'name' => 'v1.0.0',
                'date' => '2023-09-01 12:00:00',
                'hash' => 'def456',
            ],
        ]);

        $gitService->method('getCommits')->willReturnCallback(function ($from, $to) {
            if ($from === 'v1.0.0' && $to === 'v1.1.0') {
                return [
                    [
                        'hash' => 'abc123',
                        'message' => 'feat: add new feature',
                        'date' => '2023-10-01 12:00:00',
                    ],
                ];
            } elseif ($from === null && $to === 'v1.0.0') {
                return [
                    [
                        'hash' => 'def456',
                        'message' => 'fix: fix critical bug',
                        'date' => '2023-08-30 12:00:00',
                    ],
                ];
            }

            return [];
        });

        $releases = $gitService->getCommitsByRelease();
        $this->assertCount(2, $releases);

        $this->assertEquals('v1.1.0', $releases[0]['name']);
        $this->assertEquals('2023-10-02 12:00:00', $releases[0]['date']);
        $this->assertCount(1, $releases[0]['commits']);
        $this->assertEquals('abc123', $releases[0]['commits'][0]['hash']);

        $this->assertEquals('v1.0.0', $releases[1]['name']);
        $this->assertEquals('2023-09-01 12:00:00', $releases[1]['date']);
        $this->assertCount(1, $releases[1]['commits']);
        $this->assertEquals('def456', $releases[1]['commits'][0]['hash']);
    }
}
