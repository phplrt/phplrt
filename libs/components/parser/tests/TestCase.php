<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests;

use Phplrt\Parser\Builder\Analysis\ChoicePredictionConstructionParserAnalysisPass;
use Phplrt\Parser\Builder\Analysis\KeptRuleConstructionParserAnalysisPass;
use Phplrt\Parser\Builder\Analysis\StartPredictionConstructionParserAnalysisPass;
use Phplrt\Parser\Builder\Analysis\ParserResultContext;
use Phplrt\Parser\Builder\Analysis\SequencePredictionConstructionParserAnalysisPass;
use Phplrt\Parser\Builder\Definition\Reducer\CallableReducer;
use Testo\Core\Exception\SkipTest;

abstract class TestCase
{
    protected static function createNonSeekableResource(string $content = '')
    {
        $pair = @\stream_socket_pair(\STREAM_PF_INET, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);

        if ($pair === false) {
            throw new SkipTest('The platform does not support socket pairs');
        }

        [$read, $write] = $pair;

        \fwrite($write, $content);
        \fclose($write);

        return $read;
    }

    protected static function analyze(array $grammar, int $initial, array $reducers = []): ParserResultContext
    {
        $context = new ParserResultContext(
            grammar: $grammar,
            initial: $initial,
            reducers: \array_map(
                static fn(callable $reducer): CallableReducer => new CallableReducer($reducer),
                $reducers,
            ),
        );

        $passes = [
            new StartPredictionConstructionParserAnalysisPass(),
            new KeptRuleConstructionParserAnalysisPass(),
            new ChoicePredictionConstructionParserAnalysisPass(),
            new SequencePredictionConstructionParserAnalysisPass(),
        ];

        foreach ($passes as $pass) {
            $pass->process($context);
        }

        return $context;
    }
}
