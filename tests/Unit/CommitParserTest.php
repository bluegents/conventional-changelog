<?php

declare(strict_types=1);

namespace Unit;

use Bluegents\ConventionalChangelog\CommitParser;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CommitParserTest extends TestCase
{
    private CommitParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new CommitParser();
    }

    public function test_parse_simple_commit()
    {
        $commit = $this->parser->parse(
            'ABCD1234',
            'feat: add new feature',
            '2023-10-01 12:00:00'
        );

        $this->assertEquals('feat', $commit->getType());
        $this->assertEquals('add new feature', $commit->getDescription());
        $this->assertFalse($commit->isBreaking());
    }

    public function test_parse_commit_with_scope()
    {
        $commit = $this->parser->parse(
            'EFGH1234',
            'fix(core): fix critical bug',
            '2023-10-01 12:00:00'
        );

        $this->assertEquals('fix', $commit->getType());
        $this->assertEquals('core', $commit->getScope());
        $this->assertEquals('fix critical bug', $commit->getDescription());
        $this->assertFalse($commit->isBreaking());
    }

    public function test_parse_commit_with_breaking_change()
    {
        $commit = $this->parser->parse(
            'IJKL1234',
            'feat(core)!: introduce breaking change',
            '2023-10-01 12:00:00'
        );

        $this->assertEquals('feat', $commit->getType());
        $this->assertEquals('core', $commit->getScope());
        $this->assertEquals('introduce breaking change', $commit->getDescription());
        $this->assertTrue($commit->isBreaking());
    }

    public function test_parse_commit_with_body_and_footer()
    {
        $commit = $this->parser->parse(
            'MNOP1234',
            'fix: fix issue with parsing\n\nThis is the body of the commit.',
            '2023-10-01 12:00:00'
        );

        $this->assertEquals('fix', $commit->getType());
        $this->assertEquals('fix issue with parsing', $commit->getDescription());
        $this->assertEquals('This is the body of the commit.', $commit->getBody());
        $this->assertFalse($commit->isBreaking());
    }

    public function test_parse_invalid_commit()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->parser->parse(
            'QRST1234',
            'invalid commit message',
            '2023-10-01 12:00:00'
        );
    }

    public function test_parse_commit_with_date()
    {
        $commit = $this->parser->parse(
            'UVWX1234',
            'docs: update documentation',
            '2023-10-01 12:00:00'
        );

        $this->assertEquals('docs', $commit->getType());
        $this->assertEquals('update documentation', $commit->getDescription());
        $this->assertEquals(new DateTime('2023-10-01 12:00:00'), $commit->getDate());
    }

    public function test_parse_commit_with_footer()
    {
        $commit = $this->parser->parse(
            'YZAB1234',
            'chore: update dependencies\n\nBREAKING CHANGE: major version bump',
            '2023-10-01 12:00:00'
        );

        $this->assertEquals('chore', $commit->getType());
        $this->assertEquals('update dependencies', $commit->getDescription());
        $this->assertEquals('BREAKING CHANGE: major version bump', $commit->getFooter());
        $this->assertTrue($commit->isBreaking());
    }

    public function test_parse_commit_with_empty_description()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->parser->parse(
            'CDEF1234',
            'fix: ',
            '2023-10-01 12:00:00'
        );
    }

    public function test_parse_commit_with_empty_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->parser->parse(
            'GHJK1234',
            ': fix issue',
            '2023-10-01 12:00:00'
        );
    }
}
