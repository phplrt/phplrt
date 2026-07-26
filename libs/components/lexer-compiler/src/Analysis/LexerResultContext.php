<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Analysis;

use Phplrt\Compiler\Lexer\Definition\RegexModifier;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;

/**
 * Describes the lexer the analysis passes complement with the metadata.
 *
 * Token identifiers are already assigned here, so the token definitions may no
 * longer be rewritten.
 */
final class LexerResultContext
{
    public function __construct(
        /**
         * A map of token ID and its definition of the initial (non-namespaced)
         * lexer state.
         *
         * @var non-empty-array<int, TokenDefinition>
         */
        public readonly array $tokens,
        /**
         * A map of state name and its token definitions, indexed by token ID.
         *
         * @var array<non-empty-string, non-empty-array<int, TokenDefinition>>
         */
        public readonly array $states,
        /**
         * @var list<RegexModifier>
         */
        public readonly array $flags,
        /**
         * A map of token name and its ID, gathered from all states.
         *
         * @var array<non-empty-string, int>
         */
        public array $constants = [],
    ) {}
}
