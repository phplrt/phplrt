<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Compiler;

use Phplrt\Lexer\Builder\Definition\RegexModifier;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;

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
         * The token definitions of the initial (non-namespaced) lexer state.
         *
         * @var list<TokenDefinition>
         */
        public array $tokens = [],
        /**
         * A map of state name and its token definitions.
         *
         * @var array<non-empty-string, list<TokenDefinition>>
         */
        public array $states = [],
        /**
         * A map of modifier value and the modifier itself.
         *
         * @var array<non-empty-string, RegexModifier>
         */
        public array $flags = [],
    ) {}
}
