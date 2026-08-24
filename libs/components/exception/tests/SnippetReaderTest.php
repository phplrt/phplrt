<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests;

use Phplrt\Exception\Analysis\FailureResult;
use Phplrt\Exception\Analyzer;
use Phplrt\Exception\Snippet\SourceLine;
use Phplrt\Exception\SnippetReader;
use Phplrt\Exception\Tests\Stub\LexerRuntimeExceptionStub;
use Phplrt\Exception\Tests\Stub\ParserRuntimeExceptionStub;
use Phplrt\Exception\Tests\Stub\TokenStub;
use Phplrt\Position\Position;
use Phplrt\Source\StringSource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/exception')]
final class SnippetReaderTest extends TestCase
{
    private const string SOURCE = "line 1\nline 2\nline 3\nline 4\nline 5\nline 6\nline 7";

    #[TestDox('A lexical error captures the fragment its own token has been read from')]
    public function testReadsTheFragmentOfLexerException(): void
    {
        self::assertSame([
            ' #2@7: line 2',
            ' #3@14: line 3',
            '>#4@21:2-6: line 4',
            ' #5@28: line 5',
            ' #6@35: line 6',
        ], self::describe(self::read(new LexerRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 23, value: 'ne 4'),
        ))));
    }

    #[TestDox('A syntax error captures the fragment of the size it tells about')]
    public function testReadsTheFragmentOfParserException(): void
    {
        self::assertSame([
            ' #2@7: line 2',
            ' #3@14: line 3',
            '>#4@21:2-6: line 4',
            '>#5@28:0-6: line 5',
            '>#6@35:0-3: line 6',
            ' #7@42: line 7',
        ], self::describe(self::read(new ParserRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 23, value: 'n'),
            length: 15,
        ))));
    }

    #[TestDox('An error that covers no fragment captures the whole line it occurred on')]
    public function testReadsTheLineOfAnErrorWithoutFragment(): void
    {
        $info = new FailureResult(
            class: \LogicException::class,
            message: '',
            source: new StringSource(self::SOURCE),
            position: new Position(line: 4),
        );

        self::assertSame([
            ' #2@7: line 2',
            ' #3@14: line 3',
            '>#4@21:0-6: line 4',
            ' #5@28: line 5',
            ' #6@35: line 6',
        ], self::describe(new SnippetReader()->read($info)));
    }

    #[TestDox('Reads exactly N lines before and after the captured ones')]
    public function testReadsTheRequestedNumberOfLinesAround(): void
    {
        for ($lines = 0; $lines <= 3; ++$lines) {
            self::assertCount($lines * 2 + 1, self::read(new LexerRuntimeExceptionStub(
                source: new StringSource(self::SOURCE),
                token: new TokenStub(offset: 23, value: 'ne 4'),
            ), $lines));
        }
    }

    #[TestDox('Two lines around the captured ones are read by default')]
    public function testReadsTwoLinesAroundByDefault(): void
    {
        self::assertSame(2, SnippetReader::DEFAULT_LINES_AROUND);
        self::assertCount(5, self::read(new LexerRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 23, value: 'ne 4'),
        )));
    }

    #[TestDox('A negative number of lines is reduced to none of them')]
    public function testNegativeNumberOfLinesIsReducedToNone(): void
    {
        self::assertCount(1, self::read(new LexerRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 23, value: 'ne 4'),
        ), -42));
    }

    #[TestDox('The result is indexed by the line numbers')]
    public function testResultIsIndexedByLineNumbers(): void
    {
        self::assertSame([2, 3, 4, 5, 6], \array_keys(self::read(new LexerRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 23, value: 'ne 4'),
        ))));
    }

    private static function read(\Throwable $e, ?int $lines = null): array
    {
        $info = new Analyzer()->analyze($e);

        return $lines === null
            ? new SnippetReader()->read($info)
            : new SnippetReader()->read($info, $lines);
    }
}
