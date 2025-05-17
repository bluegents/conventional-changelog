<?php

declare(strict_types=1);

namespace Unit;

use Bluegents\ConventionalChangelog\Command\GenerateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateCommandTest extends TestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $command = new class () extends GenerateCommand {
            public function __construct()
            {
                parent::__construct();
                $this->setName('generate');
            }
        };

        $application = new Application('Conventional Changelog Generator', '1.0.0');
        $application->add($command);
        $application->setDefaultCommand('generate', true);

        $this->commandTester = new CommandTester($command);
    }

    public function test_execute_with_dry_run_option()
    {
        $this->commandTester->execute([
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Invalid conventional commit message', $output);
    }

    public function test_execute_with_custom_release_version()
    {
        $this->commandTester->execute([
            '--release' => '2.0.0',
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Invalid conventional commit message', $output);
    }

    public function test_execute_with_custom_output_file()
    {
        $this->commandTester->execute([
            '--output' => 'custom-changelog.md',
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Invalid conventional commit message', $output);
    }

    public function test_command_configuration()
    {
        $application = new Application('Conventional Changelog Generator', '1.0.0');
        $command = new class () extends GenerateCommand {
            public function __construct()
            {
                parent::__construct();
                $this->setName('generate');
                $this->setDescription('Generates a changelog from git history following conventional commits');
            }
        };
        $application->add($command);
        $application->setDefaultCommand('generate', true);

        $this->assertEquals('generate', $command->getName());
        $this->assertEquals('Generates a changelog from git history following conventional commits', $command->getDescription());

        $options = $command->getDefinition()->getOptions();

        $this->assertArrayHasKey('from', $options);
        $this->assertArrayHasKey('to', $options);
        $this->assertArrayHasKey('config', $options);
        $this->assertArrayHasKey('output', $options);
        $this->assertArrayHasKey('release', $options);
        $this->assertArrayHasKey('dry-run', $options);

        $this->assertEquals(null, $options['from']->getDefault());
        $this->assertEquals('HEAD', $options['to']->getDefault());
        $this->assertEquals(null, $options['config']->getDefault());
        $this->assertEquals('CHANGELOG.md', $options['output']->getDefault());
        $this->assertEquals(null, $options['release']->getDefault());
    }
}
