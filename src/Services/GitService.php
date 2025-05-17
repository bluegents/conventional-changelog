<?php

declare(strict_types=1);

namespace Bluegents\ConventionalChangelog\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class GitService
{
    public function __construct(
        private string $repositoryPath
    ) {
    }

    public function getCommits(
        ?string $from = null,
        string $to = 'HEAD'
    ): array {
        $range = $from ? "$from..$to" : $to;
        $process = $this->createProcess($range);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Git command failed: ' . $process->getErrorOutput());
        }

        return array_map(function ($line) {
            [$hash, $message, $date] = explode('|', $line, 3);

            return compact('hash', 'message', 'date');
        }, array_filter(explode("\n", $process->getOutput())));
    }

    public function getTags(): array
    {
        $process = $this->createTagsProcess();
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Git command failed: ' . $process->getErrorOutput());
        }

        $tags = [];
        foreach (array_filter(explode("\n", $process->getOutput())) as $line) {
            [$name, $date, $hash] = explode('|', $line, 3);
            $tags[] = [
                'name' => $name,
                'date' => $date,
                'hash' => $hash,
            ];
        }

        return $tags;
    }

    protected function createTagsProcess(): Process
    {
        $process = new Process([
            'git',
            'tag',
            '--sort=-v:refname',
            '--format=%(refname:short)|%(creatordate:iso)|%(objectname:short)',
        ]);
        $process->setWorkingDirectory($this->repositoryPath);

        return $process;
    }

    public function getCommitsByRelease(?string $from = null, string $to = 'HEAD'): array
    {
        $tags = $this->getTags();

        // Filter tags to only include version tags (e.g., 1.0.0, v1.0.0)
        $tags = array_filter($tags, function($tag) {
            return preg_match('/^v?\d+\.\d+\.\d+$/', $tag['name']);
        });

        // Re-index array after filtering
        $tags = array_values($tags);

        if ($from) {
            $fromIndex = array_search($from, array_column($tags, 'name'));
            if ($fromIndex !== false) {
                $tags = array_slice($tags, 0, $fromIndex + 1);
            }
        }

        if ($to !== 'HEAD') {
            $toIndex = array_search($to, array_column($tags, 'name'));
            if ($toIndex !== false) {
                $tags = array_slice($tags, $toIndex);
            }
        }

        // Add the current HEAD as a pseudo-tag if it's not already included
        if ($to === 'HEAD') {
            array_unshift($tags, [
                'name' => 'HEAD',
                'date' => date('Y-m-d H:i:s'),
                'hash' => 'HEAD',
            ]);
        }

        $releases = [];
        $tagsCount = count($tags);

        for ($i = 0; $i < $tagsCount; $i++) {
            $currentTag = $tags[$i];
            $previousTag = $tags[$i + 1] ?? null;

            $range = $previousTag ? "{$previousTag['name']}..{$currentTag['name']}" : $currentTag['name'];
            $commits = $this->getCommits($previousTag ? $previousTag['name'] : null, $currentTag['name']);

            if (! empty($commits)) {
                // Extract version number without 'v' prefix if present
                $version = $currentTag['name'];
                if (preg_match('/^v?(\d+\.\d+\.\d+)$/', $version, $matches)) {
                    $version = $matches[1];
                }

                $releases[] = [
                    'name' => $version,
                    'date' => $currentTag['date'],
                    'commits' => $commits,
                ];
            }
        }

        return $releases;
    }

    protected function createProcess(string $range): Process
    {
        $process = new Process([
            'git',
            'log',
            $range,
            '--pretty=format:%h|%s|%cd',
            '--date=iso',
        ]);
        $process->setWorkingDirectory($this->repositoryPath);

        return $process;
    }
}
