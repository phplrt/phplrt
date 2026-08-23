<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests;

use Phplrt\Parser\Builder\Analysis\ChoicePredictionConstructionParserAnalysisPass;
use Phplrt\Parser\Builder\Analysis\LookaheadConstructionParserAnalysisPass;
use Phplrt\Parser\Builder\Analysis\ParserResultContext;
use Phplrt\Parser\Builder\Analysis\KeptRuleConstructionParserAnalysisPass;
use Phplrt\Parser\Builder\Definition\Reducer\CallableReducer;
use Phplrt\Parser\Context;
use Phplrt\Parser\Grammar\RuleInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates a resource stream that cannot be rewound and already holds the
     * given content.
     *
     * @return resource
     */
    protected static function createNonSeekableResource(string $content = '')
    {
        $pair = @\stream_socket_pair(\STREAM_PF_INET, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);

        if ($pair === false) {
            self::markTestSkipped('The platform does not support socket pairs');
        }

        [$read, $write] = $pair;

        \fwrite($write, $content);
        \fclose($write);

        return $read;
    }

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
            new KeptRuleConstructionParserAnalysisPass(),
            new ChoicePredictionConstructionParserAnalysisPass(),
        ];

        foreach ($passes as $pass) {
            $pass->process($context);
        }

        return $context;
    }
}
