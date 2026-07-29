<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Declaration;

use Phplrt\Compiler\Node\Reducer\Reducer;
use Phplrt\Compiler\Node\Statement\Statement;

/**
 * Declares a rule of the parser.
 */
final readonly class RuleDeclaration extends Declaration
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * The name the rule is referred to by.
         *
         * @var non-empty-string
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
         * Contains {@see true} in case of the rule is kept in the syntax tree
         * even when it recognizes a single child
         */
        public bool $isKept = false,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            children: [$body],
            offset: $offset,
            length: $length,
        );
    }
}
