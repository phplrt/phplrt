<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Analysis;

use Phplrt\Compiler\Lexer\Definition\RegexModifier;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;

/**
 * Describes the lexer the analysis passes complement with the metadata.
 *
 * Token identifiers are already assigned here, so the token definitions may no
 * longer be rewritten. An identifier is unique across ALL states, which is why
 * everything indexed by it describes the lexer as a whole.
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
         * The pattern recognizing the tokens of the initial state.
         */
        public string $pattern = '',
        /**
         * A map of state name and the pattern recognizing its tokens.
         *
         * @var array<non-empty-string, non-empty-string>
         */
        public array $statePatterns = [],
        /**
         * A map of token ID and the channel it is emitted to.
         *
         * @var array<int, non-empty-string>
         */
        public array $channels = [],
        /**
         * A map of token ID and its name.
         *
         * @var array<int, non-empty-string>
         */
        public array $names = [],
        /**
         * A map of token ID and the state transition it triggers.
         *
         * A {@see string} value enters the named state, while a {@see null} one
         * leaves the current state.
         *
         * @var array<int, non-empty-string|null>
         */
        public array $transitions = [],
    ) {}
}
