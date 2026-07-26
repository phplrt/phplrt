<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer;

use Phplrt\Compiler\Lexer\Definition\RegexModifier;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;

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
         * @var list<RegexModifier>
         */
        public array $flags,
        /**
         * A map of token name and its ID, gathered from all states.
         *
         * @var array<non-empty-string, int>
         */
        public array $constants,
    ) {}

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
