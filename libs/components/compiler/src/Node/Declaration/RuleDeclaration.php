<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Declaration;

use Phplrt\Compiler\Node\Reducer\Reducer;
use Phplrt\Compiler\Node\Statement\Statement;

/**
 * Declares a rule of the parser.
 *
 * For example,
 * ```
 * #Sum -> { return new SumNode($children); }
 *   : Number() (::T_PLUS:: Number())*
 *   ;
 * ```
 */
final readonly class RuleDeclaration extends Declaration
{
    /**
     * @param non-empty-string $name
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The name the rule is referred to by.
         */
        public string $name,
        /**
         * What the rule recognizes.
         */
        public Statement $body,
        /**
         * Converts the rule into a node of the syntax tree, or {@see null} in
         * case of the rule is reduced to its children.
         */
        public ?Reducer $reducer = null,
        /**
         * Contains {@see true} in case of the rule is declared with the "#"
         * prefix, so it is kept in the syntax tree even when it recognizes a
         * single child
         */
        public bool $isKept = false,
        int $offset = 0,
    ) {
        parent::__construct([$body], $offset);
    }
}
