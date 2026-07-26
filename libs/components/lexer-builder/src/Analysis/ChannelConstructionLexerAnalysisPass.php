<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Analysis;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;

/**
 * Describes the channel each token is emitted to.
 *
 * The default channel is the one the reader expects anyway, so only the tokens
 * leaving it are described.
 */
final readonly class ChannelConstructionLexerAnalysisPass implements
    LexerAnalysisPassInterface
{
    public function process(LexerResultContext $context): void
    {
        $result = [];

        foreach ([$context->tokens, ...\array_values($context->states)] as $definitions) {
            /** @var TokenDefinition $definition */
            foreach ($definitions as $id => $definition) {
                $channel = $definition->channel;

                if ($channel === Channel::DEFAULT) {
                    continue;
                }

                $result[$id] = $channel->name;
            }
        }

        $context->channels = $result;
    }
}
