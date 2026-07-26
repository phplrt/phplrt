<?php

declare(strict_types=1);

namespace Phplrt\Tests\Bench\Tracing;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Phplrt\Compiler\Parser\Analysis\KeptConstructionParserAnalysisPass;
use Phplrt\Compiler\Parser\Analysis\LookaheadConstructionParserAnalysisPass;
use Phplrt\Compiler\Parser\Analysis\MergedConstructionParserAnalysisPass;
use Phplrt\Compiler\Parser\Analysis\ParserAnalysis;
use Phplrt\Parser\Parser;

#[Warmup(1)]
#[Revs(2)]
#[Iterations(2)]
#[RetryThreshold(0.3)]
#[BeforeMethods('prepare')]
final readonly class PhplrtLookaheadParsingBench extends PhplrtBench
{
    private Parser $parser;

    public function prepare(): void
    {
        $lexer = $this->getLexer();
        $grammar = $this->getParserGrammar($lexer);
        $initial = $this->getParserInitialRule();
        $reducers = $this->getParserReducers();

        $analysis = new ParserAnalysis($grammar, $initial, $reducers);

        $passes = [
            new LookaheadConstructionParserAnalysisPass(),
            new KeptConstructionParserAnalysisPass(),
            new MergedConstructionParserAnalysisPass(),
        ];

        foreach ($passes as $pass) {
            $pass->process($analysis);
        }

        $this->parser = new Parser(
            lexer: $lexer,
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            merged: $analysis->merged,
            first: $analysis->first,
            nullable: $analysis->nullable,
            kept: $analysis->kept,
            reducers: $analysis->reducers,
        );
    }

    public function benchParsing(): void
    {
        $this->parser->parse(self::SAMPLE);
    }
}
