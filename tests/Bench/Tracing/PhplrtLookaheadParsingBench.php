<?php

declare(strict_types=1);

namespace Phplrt\Tests\Bench\Tracing;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Phplrt\Parser\Builder\Analysis\TreePresenceConstructionParserAnalysisPass;
use Phplrt\Parser\Builder\Analysis\LookaheadConstructionParserAnalysisPass;
use Phplrt\Parser\Builder\Analysis\ParserResultContext;
use Phplrt\Parser\Builder\Definition\Reducer\CallableReducer;
use Phplrt\Parser\Parser;
use Phplrt\Source\Source;

#[Warmup(1)]
#[Revs(2)]
#[Iterations(3)]
#[RetryThreshold(0.2)]
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

        $context = new ParserResultContext($grammar, $initial, \array_map(
            static fn(callable $cb): CallableReducer => new CallableReducer($cb),
            $reducers,
        ));

        $passes = [
            new LookaheadConstructionParserAnalysisPass(),
            new TreePresenceConstructionParserAnalysisPass(),
        ];

        foreach ($passes as $pass) {
            $pass->process($context);
        }

        $this->parser = new Parser(
            lexer: $lexer,
            grammar: $context->grammar,
            initial: $context->initial,
            reducers: $reducers,
            startTokens: $context->startTokens,
            matchesEmptyInput: $context->matchesEmptyInput,
            presentInTree: $context->presentInTree,
        );
    }

    public function benchParsing(): void
    {
        $this->parser->parse(Source::new(self::SAMPLE));
    }
}
