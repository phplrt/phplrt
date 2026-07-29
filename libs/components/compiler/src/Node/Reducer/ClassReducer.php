<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Reducer;

/**
 * Converts a rule into an instance of the given class.
 */
final readonly class ClassReducer extends Reducer
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * The name of the class to instantiate, as it is written.
         *
         * The name is not resolved here: whether such a class exists is only
         * known where the generated parser is run.
         *
         * @var non-empty-string
         */
        public string $class,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            offset: $offset,
            length: $length,
        );
    }
}
