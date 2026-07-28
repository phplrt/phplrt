<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node;

use Phplrt\Compiler\Node\Declaration\Declaration;
use Phplrt\Compiler\Node\Reducer\Reducer;
use Phplrt\Compiler\Node\Statement\Quantifier;
use Phplrt\Compiler\Node\Statement\Statement;

/**
 * A single element of a grammar file, as it has been written.
 *
 * The tree records the source rather than its meaning: a name is kept the way
 * it is spelled and a pattern is kept the way it is typed, so anything that is
 * only wrong in context (an unknown token, an undefined rule, an invalid
 * quantifier) is still readable here and is reported later, at the position
 * the node carries.
 *
 * @phpstan-sealed Declaration|Quantifier|Reducer|Statement
 */
abstract readonly class Node
{
    public function __construct(
        /**
         * The nodes the current one is built of, in the order they are written
         * in the grammar file.
         *
         * @var list<Node>
         */
        public array $children = [],
        /**
         * The position in the grammar file the node starts at.
         *
         * @var int<0, max>
         */
        public int $offset = 0,
    ) {}
}
