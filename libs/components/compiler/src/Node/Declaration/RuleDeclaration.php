<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Declaration;

use Phplrt\Compiler\Node\Annotation;
use Phplrt\Compiler\Node\Reducer\Reducer;
use Phplrt\Compiler\Node\Statement\Statement;

/**
 * Declares a rule of the parser.
 *
 * @readonly
 */
final class RuleDeclaration extends Declaration
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
        public readonly string $name,
        /**
         * What the rule recognizes.
         */
        public readonly Statement $body,
        /**
         * Converts the rule into a node of the syntax tree, or {@see null} in
         * case of the rule is reduced to its children.
         */
        public readonly ?Reducer $reducer = null,
        /**
         * Contains {@see true} in case of the name of the rule is kept on the
         * compiled parser, so the analysis may be started at the rule
         */
        public readonly bool $isKept = false,
        /**
         * What is said about the rule apart from what it recognizes, in the
         * order it is written.
         *
         * @var list<Annotation>
         */
        public readonly array $annotations = [],
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
