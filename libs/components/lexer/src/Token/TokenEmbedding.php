<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * The token that has entered an embedded lexer, along with everything that
 * lexer has read.
 *
 * The token is written the way it is written in the source, so its value is
 * the fragment that entered the embedded lexer rather than the fragment read
 * by it: where the reading has stopped is told by {@see TokenEmbedding::$end}.
 */
final readonly class TokenEmbedding extends CompositeToken
{
    /**
     * Creates the embedding of the given token.
     *
     * @param list<TokenInterface> $children
     * @param int<0, max> $end
     */
    public static function createFromToken(TokenInterface $token, array $children, int $end): self
    {
        return new self(
            id: $token->id,
            name: $token->name,
            channel: $token->channel,
            value: $token->value,
            offset: $token->offset,
            end: $end,
            children: $children,
        );
    }
}
