<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Declaration;

/**
 * Pulls the declarations of another grammar file into this one.
 */
final readonly class IncludeDeclaration extends Declaration
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * The pathname of the grammar file to read, as it is written.
         *
         * The pathname is relative to the file the declaration belongs to and
         * the extension may be omitted, so it cannot be resolved without
         * knowing which file has been read.
         *
         * @var non-empty-string
         */
        public string $target,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            offset: $offset,
            length: $length,
        );
    }
}
