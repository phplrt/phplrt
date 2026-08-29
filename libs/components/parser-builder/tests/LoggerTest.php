<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Tests;

use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Builder\Tests\Stub\InMemoryLogger;
use Psr\Log\NullLogger;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/parser-compiler')]
#[Test]
final class LoggerTest extends TestCase
{
    public function testNothingIsReportedByDefault(): void
    {
        Assert::instanceOf(new ParserBuilder()->logger, NullLogger::class);
    }

    public function testUnreachableRuleIsReported(): void
    {
        $logger = new InMemoryLogger();

        $parser = new ParserBuilder();
        $parser->setLogger($logger);

        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addTokenReference('T_PLUS'),
        ], 'Expression'));

        $parser->addConcatenation([
            $parser->addTokenReference('T_MINUS'),
        ], 'Unused');

        $parser->build(self::createLexerBuilder()->build());

        Assert::contains(
            $logger->records,
            'info: Rule Unused = <name is "T_MINUS"> is removed, since it is not reachable from Expression',
        );
    }

    public function testRepeatedAlternativeIsReported(): void
    {
        $logger = new InMemoryLogger();

        $parser = new ParserBuilder();
        $parser->setLogger($logger);

        $number = $parser->addTokenReference('T_NUMBER');

        $parser->setInitialRule($parser->addAlternation([$number, $number], 'Value'));

        $parser->build(self::createLexerBuilder()->build());

        Assert::contains(
            $logger->records,
            'info: Alternation Value has lost 1 alternative(s) repeating an earlier one',
        );
    }

    public function testBuildBoundariesAreReported(): void
    {
        $logger = new InMemoryLogger();

        $parser = new ParserBuilder();
        $parser->setLogger($logger);

        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
            $parser->addTokenReference('T_PLUS'),
        ]));

        $parser->build(self::createLexerBuilder()->build());

        Assert::contains($logger->records, 'info: Building a parser out of 3 rule(s)');
        Assert::contains($logger->records, 'info: The parser of 3 rule(s) has been built');
    }
}
