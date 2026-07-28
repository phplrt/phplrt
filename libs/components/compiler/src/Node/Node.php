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
 * Every grammar format is read into this very tree, so a node says what an
 * element means and never how it is spelled: the spelling belongs to the
 * format that has been read and lives with the parser reading it.
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
        /**
         * The number of bytes of the grammar file the node is written of, or
         * "0" in case of the node ends where it starts.
         *
         * @var int<0, max>
         */
        public int $length = 0,
    ) {}
}
