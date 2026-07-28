<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Reducer;

/**
 * Converts a rule into an instance of the given class.
 *
 * For example,
 * ```
 * Sum -> \App\Node\SumNode
 *   : ...
 * ```
 */
final readonly class ClassReducer extends Reducer
{
    /**
     * @param non-empty-string $class
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The name of the class to instantiate, as it is written.
         *
         * The name is not resolved here: whether such a class exists is only
         * known where the generated parser is run.
         */
        public string $class,
        int $offset = 0,
    ) {
        parent::__construct(offset: $offset);
    }
}
