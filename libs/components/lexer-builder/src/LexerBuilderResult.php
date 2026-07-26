<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Transformer\RuntimeLexerTransformer;

/**
 * Represents the result of building a lexer.
 *
 * Token identifiers are unique across ALL states, so a token ID is always
 * enough to distinguish a token, no matter which state produced it.
 */
final readonly class LexerBuilderResult
{
    public function __construct(
        /**
         * A map of token ID and its definition of the initial (non-namespaced)
         * lexer state.
         *
         * @var non-empty-array<int, TokenDefinition>
         */
        public array $tokens,
        /**
         * A map of state name and its token definitions, indexed by token ID.
         *
         * @var array<non-empty-string, non-empty-array<int, TokenDefinition>>
         */
        public array $states,
        /**
         * The pattern recognizing the tokens of the initial state.
         *
         * @var non-empty-string
         */
        public string $pattern,
        /**
         * A map of state name and the pattern recognizing its tokens.
         *
         * @var array<non-empty-string, non-empty-string>
         */
        public array $statePatterns,
        /**
         * A map of token ID and the channel it is emitted to.
         *
         * @var array<int, non-empty-string>
         */
        public array $channels,
        /**
         * A map of token ID and its name.
         *
         * @var array<int, non-empty-string>
         */
        public array $names,
        /**
         * A map of token ID and the state transition it triggers.
         *
         * A {@see string} value enters the named state, while a {@see null} one
         * leaves the current state.
         *
         * @var array<int, non-empty-string|null>
         */
        public array $transitions,
    ) {}

    public function toLexer(): LexerInterface
    {
        return new RuntimeLexerTransformer()
            ->transform($this);
    }

    /**
     * Returns the identifier of the given token definition, or {@see null} in
     * case of the token is not recognized by the lexer.
     *
     * @api
     */
    public function findTokenId(TokenDefinition $token): ?int
    {
        foreach ([$this->tokens, ...\array_values($this->states)] as $definitions) {
            $id = \array_search($token, $definitions, true);

            if ($id !== false) {
                return $id;
            }
        }

        return null;
    }
}
