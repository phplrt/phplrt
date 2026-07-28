<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Reducer;

/**
 * Converts a rule into a node of the syntax tree using the given PHP code.
 *
 * For example,
 * ```
 * Sum -> { return new SumNode($children); }
 *   : ...
 * ```
 */
final readonly class CodeReducer extends Reducer
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The code written between the braces, as it is typed.
         *
         * The code is the body of a callback, so the variables it refers to
         * and the value it returns are the business of the generator. An empty
         * body ("-> {}") is written the same way a missing reducer is meant.
         */
        public string $code,
        int $offset = 0,
    ) {
        parent::__construct(offset: $offset);
    }
}
