<?php

declare(strict_types=1);

namespace Bluegents\ConventionalChangelog\Command;

use Bluegents\ConventionalChangelog\CommitParser;
use Bluegents\ConventionalChangelog\Configuration;
use Bluegents\ConventionalChangelog\Generators\ChangeLogGenerator;
use Bluegents\ConventionalChangelog\Generators\VersionGenerator;
use Bluegents\ConventionalChangelog\Services\GitService;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class GenerateCommand extends Command
{
    protected static $defaultName = 'generate';
    protected static $defaultDescription = 'Generates a changelog from git history following conventional commits';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'from',
                null,
                InputOption::VALUE_REQUIRED,
                'Start commit/tag to generate from',
                null
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_REQUIRED,
                'End commit/tag to generate to',
                'HEAD'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                'Path to config file',
                null
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Output file path',
                'CHANGELOG.md'
            )
            ->addOption(
                'release',
                'r',
                InputOption::VALUE_REQUIRED,
                'Release version (auto-detected if not provided)',
                null
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Output to console instead of file'
            )
            ->addOption(
                'multi-release',
                'm',
                InputOption::VALUE_NONE,
                'Generate changelog for multiple releases'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $config = $this->loadConfiguration($input);

            $gitService = $this->createGitService();
            $commitParser = new CommitParser();
            $changelogGenerator = new ChangelogGenerator($config, $commitParser);
            $versionGenerator = new VersionGenerator();

            $multiRelease = $input->getOption('multi-release');

            if ($multiRelease) {
                $releases = $gitService->getCommitsByRelease($input->getOption('from'), $input->getOption('to'));

                if (empty($releases)) {
                    $output->writeln('<info>No releases found in the specified range.</info>');

                    return Command::SUCCESS;
                }

                $changelog = $changelogGenerator->generateMultiRelease($releases);

                if ($input->getOption('dry-run')) {
                    $output->writeln($changelog);
                } else {
                    $outputFile = $input->getOption('output');
                    file_put_contents($outputFile, $changelog);
                    $output->writeln(sprintf(
                        '<info>Changelog generated successfully for %d releases and written to %s</info>',
                        count($releases),
                        $outputFile
                    ));
                }
            } else {
                $gitCommits = $gitService->getCommits($input->getOption('from'), $input->getOption('to'));

                if (empty($gitCommits)) {
                    $output->writeln('<info>No commits found in the specified range.</info>');

                    return Command::SUCCESS;
                }

                $releaseVersion = $input->getOption('release') ?: $this->detectVersion(
                    $versionGenerator,
                    $commitParser,
                    $gitCommits,
                    $output
                );

                $changelog = $changelogGenerator->generate($gitCommits, $releaseVersion);

                if ($input->getOption('dry-run')) {
                    $output->writeln($changelog);
                } else {
                    $outputFile = $input->getOption('output');
                    file_put_contents($outputFile, $changelog);
                    $output->writeln(sprintf(
                        '<info>Changelog generated successfully for version %s and written to %s</info>',
                        $releaseVersion,
                        $outputFile
                    ));
                }
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }
    }

    private function loadConfiguration(InputInterface $input): Configuration
    {
        $config = new Configuration();

        if ($configFile = $input->getOption('config')) {
            if (! file_exists($configFile)) {
                throw new RuntimeException(sprintf('Config file not found: %s', $configFile));
            }

            $configData = json_decode(file_get_contents($configFile), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Invalid JSON in config file: ' . json_last_error_msg());
            }

            foreach ($configData as $key => $value) {
                $config->set($key, $value);
            }
        }

        if ($input->getOption('output')) {
            $config->set('output_file', $input->getOption('output'));
        }

        return $config;
    }

    private function detectVersion(
        VersionGenerator $versionGenerator,
        CommitParser $commitParser,
        array $gitCommits,
        OutputInterface $output
    ): string {
        $commits = array_map(
            fn (array $gitCommit) => $commitParser->parse(
                $gitCommit['hash'],
                $gitCommit['message'],
                $gitCommit['date']
            ),
            $gitCommits
        );

        $currentVersion = $this->getLatestVersionFromGit();
        if ($currentVersion === null) {
            $output->writeln('<comment>No version tags found, starting with 0.1.0</comment>');
            $currentVersion = '0.1.0';
        }

        return $versionGenerator->determineNextVersion($currentVersion, $commits);
    }

    private function getLatestVersionFromGit(): ?string
    {
        $process = new Process(['git', 'tag', '-l', '--sort=-v:refname']);
        $process->setWorkingDirectory(getcwd());
        $process->run();

        if ($process->isSuccessful()) {
            $tags = array_filter(explode("\n", $process->getOutput()));
            foreach ($tags as $tag) {
                if (preg_match('/^v?(\d+\.\d+\.\d+)$/', $tag, $matches)) {
                    return $matches[1];
                }
            }
        }

        return null;
    }

    protected function createGitService(): GitService
    {
        return new GitService(getcwd());
    }
}
