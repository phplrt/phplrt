<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests;

use Phplrt\Compiler\Parser\Analysis\TreePresenceConstructionParserAnalysisPass;
use Phplrt\Compiler\Parser\Analysis\LookaheadConstructionParserAnalysisPass;
use Phplrt\Compiler\Parser\Analysis\ParserResultContext;
use Phplrt\Compiler\Parser\Definition\Reducer\CallableReducer;
use Phplrt\Parser\Context;
use Phplrt\Parser\Grammar\RuleInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Describes the given grammar the same way the parser compiler does.
     *
     * @param list<RuleInterface> $grammar
     * @param int<0, max> $initial
     * @param array<int<0, max>, callable(Context, mixed): mixed> $reducers
     */
    protected static function analyze(array $grammar, int $initial, array $reducers = []): ParserResultContext
    {
        $context = new ParserResultContext($grammar, $initial, \array_map(
            static fn(callable $reducer): CallableReducer => new CallableReducer($reducer),
            $reducers,
        ));

        $passes = [
            new LookaheadConstructionParserAnalysisPass(),
            new TreePresenceConstructionParserAnalysisPass(),
        ];

        foreach ($passes as $pass) {
            $pass->process($context);
        }

        return $context;
    }
}
