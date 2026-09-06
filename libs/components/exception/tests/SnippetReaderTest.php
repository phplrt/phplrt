<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests;

use Phplrt\Exception\Analysis\FailureResult;
use Phplrt\Exception\Analyzer;
use Phplrt\Exception\SnippetReader;
use Phplrt\Exception\Tests\Stub\LexerRuntimeExceptionStub;
use Phplrt\Exception\Tests\Stub\ParserRuntimeExceptionStub;
use Phplrt\Exception\Tests\Stub\TokenStub;
use Phplrt\Position\Position;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/exception')]
#[Test]
final class SnippetReaderTest extends TestCase
{
    private const SOURCE = "line 1\nline 2\nline 3\nline 4\nline 5\nline 6\nline 7";

    public function testReadsTheFragmentOfLexerException(): void
    {
        Assert::same(self::describe(self::read(new LexerRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 23, value: 'ne 4'),
        ))), [
            ' #2@7: line 2',
            ' #3@14: line 3',
            '>#4@21:2-6: line 4',
            ' #5@28: line 5',
            ' #6@35: line 6',
        ]);
    }

    public function testReadsTheFragmentOfParserException(): void
    {
        Assert::same(self::describe(self::read(new ParserRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 23, value: 'n'),
            length: 15,
        ))), [
            ' #2@7: line 2',
            ' #3@14: line 3',
            '>#4@21:2-6: line 4',
            '>#5@28:0-6: line 5',
            '>#6@35:0-3: line 6',
            ' #7@42: line 7',
        ]);
    }

    public function testReadsTheLineOfAnErrorWithoutFragment(): void
    {
        $info = new FailureResult(
            class: \LogicException::class,
            message: '',
            source: new StringSource(self::SOURCE),
            position: new Position(line: 4),
        );

        Assert::same(self::describe((new SnippetReader())->read($info)), [
            ' #2@7: line 2',
            ' #3@14: line 3',
            '>#4@21:0-6: line 4',
            ' #5@28: line 5',
            ' #6@35: line 6',
        ]);
    }

    public function testReadsTheRequestedNumberOfLinesAround(): void
    {
        for ($lines = 0; $lines <= 3; ++$lines) {
            Assert::count(self::read(new LexerRuntimeExceptionStub(
                source: new StringSource(self::SOURCE),
                token: new TokenStub(offset: 23, value: 'ne 4'),
            ), $lines), $lines * 2 + 1);
        }
    }

    public function testReadsTwoLinesAroundByDefault(): void
    {
        Assert::same(SnippetReader::DEFAULT_LINES_AROUND, 2);
        Assert::count(self::read(new LexerRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 23, value: 'ne 4'),
        )), 5);
    }

    public function testNegativeNumberOfLinesIsReducedToNone(): void
    {
        Assert::count(self::read(new LexerRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 23, value: 'ne 4'),
        ), -42), 1);
    }

    public function testResultIsIndexedByLineNumbers(): void
    {
        Assert::same(\array_keys(self::read(new LexerRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 23, value: 'ne 4'),
        ))), [2, 3, 4, 5, 6]);
    }

    private static function read(\Throwable $e, ?int $lines = null): array
    {
        $info = (new Analyzer())->analyze($e);

        return $lines === null
            ? (new SnippetReader())->read($info)
            : (new SnippetReader())->read($info, $lines);
    }
}
