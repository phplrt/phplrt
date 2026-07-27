<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Analysis;

use Phplrt\Contracts\Lexer\Channel;

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

        foreach ($context->tokens as $id => $definition) {
            $channel = $definition->channel;

            if ($channel === Channel::DEFAULT) {
                continue;
            }

            $result[$id] = $channel->name;
        }

        $context->channels = $result;
    }
}
