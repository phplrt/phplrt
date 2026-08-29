<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Exception\Analysis\FailureLevel;
use Phplrt\Exception\Analyzer;
use Phplrt\Exception\Tests\Stub\FilelessExceptionStub;
use Phplrt\Exception\Tests\Stub\LexerRuntimeExceptionStub;
use Phplrt\Exception\Tests\Stub\ParserRuntimeExceptionStub;
use Phplrt\Exception\Tests\Stub\TokenStub;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/exception')]
#[Test]
final class AnalyzerTest extends TestCase
{
    private const string SOURCE = "line 1\nline 2\nline 3\nline 4";

    public function testNameAndMessageAreGivenBack(): void
    {
        $result = new Analyzer()->analyze(new \LogicException('Something went wrong'));

        Assert::same($result->class, \LogicException::class);
        Assert::same($result->message, 'Something went wrong');
    }

    public function testSeverityOfArbitraryExceptionIsTheDefaultOne(): void
    {
        Assert::same(new Analyzer()->analyze(new \LogicException())->level, FailureLevel::DEFAULT);
    }

    public function testSeverityIsTakenFromTheError(): void
    {
        $result = new Analyzer()->analyze(new \ErrorException('', severity: \E_USER_WARNING));

        Assert::same($result->level, FailureLevel::Warning);
    }

    public function testSourceOfArbitraryExceptionIsItsOwnFile(): void
    {
        $info = new Analyzer()->analyze(new \LogicException());

        Assert::instanceOf($info->source, FileInterface::class);
        Assert::same($info->source->pathname, __FILE__);
    }

    public function testPositionOfArbitraryExceptionIsItsOwnLine(): void
    {
        $line = __LINE__ + 1;
        $info = new Analyzer()->analyze(new \LogicException());

        Assert::same($info->position->line, $line);
        Assert::same($info->position->column, 1);
    }

    public function testArbitraryExceptionHasNoInterval(): void
    {
        Assert::null(new Analyzer()->analyze(new \LogicException())->interval);
    }

    public function testExceptionWithoutFileIsLocatedInEmptySource(): void
    {
        $info = new Analyzer()->analyze(new FilelessExceptionStub());

        Assert::false($info->source instanceof FileInterface);
        Assert::same($info->source->content, '');
        Assert::same($info->position->line, 1);
        Assert::same($info->position->column, 1);
    }

    public function testSourceOfLexerExceptionIsTheAnalyzedOne(): void
    {
        $source = new StringSource(self::SOURCE);

        $info = new Analyzer()->analyze(new LexerRuntimeExceptionStub(
            source: $source,
            token: new TokenStub(offset: 16, value: 'ne 3'),
        ));

        Assert::same($info->source, $source);
    }

    public function testPositionOfLexerExceptionIsTheOneOfItsToken(): void
    {
        $info = new Analyzer()->analyze(new LexerRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 16, value: 'ne 3'),
        ));

        Assert::same($info->position->line, 3);
        Assert::same($info->position->column, 3);
    }

    public function testIntervalOfLexerExceptionIsTheOneOfItsToken(): void
    {
        $info = new Analyzer()->analyze(new LexerRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 16, value: 'ne 3'),
        ));

        Assert::notNull($info->interval);
        Assert::same($info->interval->offset, 16);
        Assert::same($info->interval->length, 4);
    }

    public function testIntervalOfParserExceptionIsTheOneItTellsAbout(): void
    {
        $info = new Analyzer()->analyze(new ParserRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 14, value: 'line'),
            length: 12,
        ));

        Assert::notNull($info->interval);
        Assert::same($info->interval->offset, 14);
        Assert::same($info->interval->length, 12);
    }

    public function testIntervalOfParserExceptionFallsBackToItsToken(): void
    {
        $info = new Analyzer()->analyze(new ParserRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 14, value: 'line'),
        ));

        Assert::notNull($info->interval);
        Assert::same($info->interval->offset, 14);
        Assert::same($info->interval->length, 4);
    }

    public function testPositionOfParserExceptionIsTheBeginningOfItsFragment(): void
    {
        $info = new Analyzer()->analyze(new ParserRuntimeExceptionStub(
            source: new StringSource(self::SOURCE),
            token: new TokenStub(offset: 14, value: 'line'),
            length: 12,
        ));

        Assert::same($info->position->line, 3);
        Assert::same($info->position->column, 1);
    }

    public function testTheOnlyExceptionHasNoPrevious(): void
    {
        Assert::null(new Analyzer()->analyze(new \LogicException())->previous);
    }

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

        Assert::null($info->interval);

        Assert::notNull($info->previous);
        Assert::same($info->previous->class, ParserRuntimeExceptionStub::class);
        Assert::same($info->previous->position->line, 4);

        Assert::notNull($info->previous->previous);
        Assert::same($info->previous->previous->class, LexerRuntimeExceptionStub::class);
        Assert::same($info->previous->previous->position->line, 2);

        Assert::null($info->previous->previous->previous);
    }

    public function testChainOfAnyLengthIsDescribed(): void
    {
        $exception = new \LogicException('#0');

        for ($i = 1; $i < 1000; ++$i) {
            $exception = new \LogicException('#' . $i, 0, $exception);
        }

        $info = new Analyzer()->analyze($exception);

        for ($i = 999; $i > 0; --$i) {
            Assert::same($info->message, '#' . $i);
            Assert::notNull($info->previous);

            $info = $info->previous;
        }

        Assert::same($info->message, '#0');
        Assert::null($info->previous);
    }
}
