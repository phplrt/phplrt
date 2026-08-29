<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Lexer\Builder\Tests\Stub\InMemoryLogger;
use Psr\Log\NullLogger;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/lexer-compiler')]
#[Test]
final class LoggerTest extends TestCase
{
    public function testNothingIsReportedByDefault(): void
    {
        Assert::instanceOf(new LexerBuilder()->logger, NullLogger::class);
    }

    public function testUnreachableLexerIsReported(): void
    {
        $logger = new InMemoryLogger();

        $lexer = new LexerBuilder();
        $lexer->setLogger($logger);
        $lexer->addPattern('\d++', 'T_NUMBER');
        $lexer->addLexer('unused')->addPattern('[^"]++');

        $lexer->build();

        Assert::contains(
            $logger->records,
            'info: Lexer unused is removed, since nothing hands the reading over to it',
        );
    }

    public function testFragmentSubstitutionIsReported(): void
    {
        $logger = new InMemoryLogger();

        $lexer = new LexerBuilder();
        $lexer->setLogger($logger);
        $lexer->addFragment('DIGIT', '[0-9]');
        $lexer->addPattern('(?&DIGIT)++', 'T_NUMBER');

        $lexer->build();

        Assert::contains(
            $logger->records,
            'debug: Token /(?&DIGIT)++/ (T_NUMBER) refers to the fragments it is now written of: (?:[0-9])++',
        );
    }

    public function testBuildBoundariesAreReported(): void
    {
        $logger = new InMemoryLogger();

        $lexer = new LexerBuilder();
        $lexer->setLogger($logger);
        $lexer->addPattern('\d++', 'T_NUMBER');

        $lexer->build();

        Assert::contains($logger->records, 'info: Building a lexer out of 1 token(s)');
        Assert::contains($logger->records, 'info: The lexer of 2 token(s) has been built');
    }
}
