<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Exception\Analyzer;
use Phplrt\Exception\Tests\Stub\FilelessExceptionStub;
use Phplrt\Exception\Tests\Stub\LexerRuntimeExceptionStub;
use Phplrt\Exception\Tests\Stub\ParserRuntimeExceptionStub;
use Phplrt\Exception\Tests\Stub\TokenStub;
use Phplrt\Source\StringSource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/exception')]
final class AnalyzerTest extends TestCase
{
    /**
     * Each line of the source is 6 bytes long, so with a single-byte
     * delimiter the line number N starts at offset N - 1 multiplied by 7.
     *
     * @var non-empty-string
     */
    private const string SOURCE = "line 1\nline 2\nline 3\nline 4";

    #[TestDox('The original error is given back as is')]
    public function testExceptionIsGivenBack(): void
    {
        $expected = new \LogicException('Something went wrong');

        self::assertSame($expected, new Analyzer()->analyze($expected)->exception);
    }

    #[TestDox('An error that refers to no source is located in the file it has been thrown from')]
    public function testSourceOfArbitraryExceptionIsItsOwnFile(): void
    {
        $info = new Analyzer()->analyze(new \LogicException());

        self::assertInstanceOf(FileInterface::class, $info->source);
        self::assertSame(__FILE__, $info->source->pathname);
    }

    #[TestDox('An error that refers to no source is located on the line it has been thrown from')]
    public function testPositionOfArbitraryExceptionIsItsOwnLine(): void
    {
        $line = __LINE__ + 1;
        $info = new Analyzer()->analyze(new \LogicException());

        self::assertSame($line, $info->position->line);
        self::assertSame(1, $info->position->column);
    }

    #[TestDox('An error that refers to no source covers no fragment of it')]
    public function testArbitraryExceptionHasNoInterval(): void
    {
        self::assertNull(new Analyzer()->analyze(new \LogicException())->interval);
    }

    #[TestDox('An error thrown outside any file is located in an empty source')]
    public function testExceptionWithoutFileIsLocatedInEmptySource(): void
    {
        $info = new Analyzer()->analyze(new FilelessExceptionStub());

        self::assertNotInstanceOf(FileInterface::class, $info->source);
        self::assertSame('', $info->source->content);
        self::assertSame(1, $info->position->line);
        self::assertSame(1, $info->position->column);
    }

    #[TestDox('A lexical error is located in the source it has been thrown for')]
    public function testSourceOfLexerExceptionIsTheAnalyzedOne(): void
    {
        $source = new StringSource(self::SOURCE);

        $info = new Analyzer()->analyze(new LexerRuntimeExceptionStub(
            source: $source,
            token: new TokenStub(offset: 16, value: 'ne 3'),
        ));

        self::assertSame($source, $info->source);
    }

    #[TestDox('A lexical error is located at the position of its own token')]
    public function testPositionOfLexerExceptionIsTheOneOfItsToken(): void
    {
        $info = new Analyzer()->analyze(new LexerRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 16, value: 'ne 3'),
        ));

        self::assertSame(3, $info->position->line);
        self::assertSame(3, $info->position->column);
    }

    #[TestDox('A lexical error covers the fragment its own token has been read from')]
    public function testIntervalOfLexerExceptionIsTheOneOfItsToken(): void
    {
        $info = new Analyzer()->analyze(new LexerRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 16, value: 'ne 3'),
        ));

        self::assertNotNull($info->interval);
        self::assertSame(16, $info->interval->offset);
        self::assertSame(4, $info->interval->length);
    }

    #[TestDox('A syntax error covers the fragment of the size it tells about')]
    public function testIntervalOfParserExceptionIsTheOneItTellsAbout(): void
    {
        $info = new Analyzer()->analyze(new ParserRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 14, value: 'line'),
            length: 12,
        ));

        self::assertNotNull($info->interval);
        self::assertSame(14, $info->interval->offset);
        self::assertSame(12, $info->interval->length);
    }

    #[TestDox('A syntax error of an unknown size covers the fragment its own token has been read from')]
    public function testIntervalOfParserExceptionFallsBackToItsToken(): void
    {
        $info = new Analyzer()->analyze(new ParserRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 14, value: 'line'),
        ));

        self::assertNotNull($info->interval);
        self::assertSame(14, $info->interval->offset);
        self::assertSame(4, $info->interval->length);
    }

    #[TestDox('A syntax error is located at the position its own fragment starts at')]
    public function testPositionOfParserExceptionIsTheBeginningOfItsFragment(): void
    {
        $info = new Analyzer()->analyze(new ParserRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 14, value: 'line'),
            length: 12,
        ));

        self::assertSame(3, $info->position->line);
        self::assertSame(1, $info->position->column);
    }

    #[TestDox('An error that no other one has led to refers to no previous information')]
    public function testTheOnlyExceptionHasNoPrevious(): void
    {
        self::assertNull(new Analyzer()->analyze(new \LogicException())->previous);
    }

    #[TestDox('Every error of the chain is described, from the outermost one to the innermost')]
    public function testEveryExceptionOfTheChainIsDescribed(): void
    {
        $source = new StringSource(self::SOURCE);

        $inner = new LexerRuntimeExceptionStub(
            source: $source,
            token: new TokenStub(offset: 7, value: 'line 2'),
        );

        $outer = new ParserRuntimeExceptionStub(
            source: $source,
            token: new TokenStub(offset: 21, value: 'line 4'),
            previous: $inner,
        );

        $info = new Analyzer()->analyze(new \LogicException('Compilation failed', 0, $outer));

        self::assertNull($info->interval);

        self::assertNotNull($info->previous);
        self::assertSame($outer, $info->previous->exception);
        self::assertSame(4, $info->previous->position->line);

        self::assertNotNull($info->previous->previous);
        self::assertSame($inner, $info->previous->previous->exception);
        self::assertSame(2, $info->previous->previous->position->line);

        self::assertNull($info->previous->previous->previous);
    }

    #[TestDox('A chain of any length is described without a recursion')]
    public function testChainOfAnyLengthIsDescribed(): void
    {
        $exception = new \LogicException('#0');

        for ($i = 1; $i < 1000; ++$i) {
            $exception = new \LogicException('#' . $i, 0, $exception);
        }

        $info = new Analyzer()->analyze($exception);

        for ($i = 999; $i > 0; --$i) {
            self::assertSame('#' . $i, $info->exception->getMessage());
            self::assertNotNull($info->previous);

            $info = $info->previous;
        }

        self::assertSame('#0', $info->exception->getMessage());
        self::assertNull($info->previous);
    }
}
