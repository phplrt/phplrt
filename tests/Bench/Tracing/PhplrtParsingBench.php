<?php

declare(strict_types=1);

namespace Phplrt\Tests\Bench\Tracing;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Phplrt\Parser\Parser;
use Phplrt\Source\Source;

#[Warmup(1)]
#[Revs(2)]
#[Iterations(3)]
#[RetryThreshold(0.2)]
#[BeforeMethods('prepare')]
final readonly class PhplrtParsingBench extends PhplrtBench
{
    private Parser $parser;

    public function prepare(): void
    {
        $lexer = $this->getLexer();

        $this->parser = new Parser(
            lexer: $lexer,
            grammar: $this->getParserGrammar($lexer),
            initial: $this->getParserInitialRule(),
            reducers: $this->getParserReducers(),
        );
    }

    public function benchParsing(): void
    {
        $this->parser->parse(new Source(self::SAMPLE));
    }
}
