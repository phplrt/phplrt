<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Analysis;

use Phplrt\Lexer\Builder\Definition\Lexer\EmbeddedLexerInterface;
use Phplrt\Lexer\Builder\Definition\RegexModifier;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\LexerBuilderResult;

/**
 * Describes the lexer the analysis passes complement with the metadata.
 *
 * Token identifiers are already assigned here, so the token definitions may no
 * longer be rewritten. An identifier only means something inside this lexer:
 * the tokens read by another one never reach its stream.
 */
final class LexerResultContext
{
    public function __construct(
        /**
         * The token definitions the lexer recognizes on its own, indexed by
         * token ID.
         *
         * @var non-empty-array<int, TokenDefinition>
         */
        public readonly array $tokens,
        /**
         * @var list<RegexModifier>
         */
        public readonly array $flags,
        /**
         * A map of name and the lexer reading the fragment it stands for.
         *
         * @var array<non-empty-string, LexerBuilderResult|EmbeddedLexerInterface>
         */
        public readonly array $lexers = [],
        /**
         * The pattern recognizing the tokens of the lexer.
         */
        public string $pattern = '',
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
         * A map of token ID and what the token does to the reading.
         *
         * A {@see string} value hands the reading over to the named lexer,
         * while a {@see null} one ends the reading of this one.
         *
         * @var array<int, non-empty-string|null>
         */
        public array $transitions = [],
    ) {}
}
