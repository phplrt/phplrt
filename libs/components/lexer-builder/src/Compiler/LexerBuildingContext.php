<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Compiler;

use Phplrt\Lexer\Builder\Definition\Lexer\EmbeddedLexerInterface;
use Phplrt\Lexer\Builder\Definition\RegexModifier;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\LexerBuilder;

/**
 * Contains the token definitions the compiler passes rewrite and check.
 *
 * The token definitions themselves are shared with the builder they have been
 * copied from: a definition is what a rule of a parser refers to, so replacing
 * it by a copy would break that link.
 */
final class LexerBuildingContext
{
    public function __construct(
        /**
         * The token definitions the lexer recognizes on its own.
         *
         * @var list<TokenDefinition>
         */
        public array $tokens = [],
        /**
         * A map of name and the lexer reading the fragment it stands for.
         *
         * @var array<non-empty-string, LexerBuilder|EmbeddedLexerInterface>
         */
        public array $lexers = [],
        /**
         * A map of modifier value and the modifier itself.
         *
         * @var array<non-empty-string, RegexModifier>
         */
        public array $flags = [],
        /**
         * Contains {@see true} in case of the lexer is called by another one,
         * so it is allowed to stop reading and give the control back
         */
        public bool $isEmbedded = false,
    ) {}
}
