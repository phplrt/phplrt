<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\Tests\Stub\InMemoryLogger;
use Phplrt\Source\FileSource;
use Psr\Log\NullLogger;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/compiler')]
#[Test]
final class LoggerTest extends TestCase
{
    public function testNothingIsReportedByDefault(): void
    {
        $compiler = new Compiler();

        Assert::instanceOf($compiler->logger, NullLogger::class);
        Assert::instanceOf($compiler->parser->logger, NullLogger::class);
        Assert::instanceOf($compiler->lexer->logger, NullLogger::class);
    }

    public function testLoggerIsSharedWithBuilders(): void
    {
        $logger = new InMemoryLogger();

        $compiler = new Compiler();
        $compiler->setLogger($logger);

        Assert::same($compiler->logger, $logger);
        Assert::same($compiler->parser->logger, $logger);
        Assert::same($compiler->lexer->logger, $logger);
    }

    public function testReadGrammarIsReported(): void
    {
        $logger = new InMemoryLogger();

        $compiler = new Compiler();
        $compiler->setLogger($logger);

        $pathname = __DIR__ . '/resources/grammar.pp2';

        $compiler->load(FileSource::createFromPathname($pathname));
        $compiler->build();

        Assert::contains($logger->records, \sprintf('info: Reading the %s grammar', $pathname));
        Assert::contains($logger->records, 'info: Compiling the grammar that has been read');
    }
}
