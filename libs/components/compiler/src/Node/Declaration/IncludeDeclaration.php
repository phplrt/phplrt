<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Declaration;

/**
 * Pulls the declarations of another grammar file into this one.
 *
 * For example,
 * ```
 * %include grammar/lexemes
 * ```
 */
final readonly class IncludeDeclaration extends Declaration
{
    /**
     * @param non-empty-string $target
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The pathname of the grammar file to read, as it is written.
         *
         * The pathname is relative to the file the declaration belongs to and
         * the extension may be omitted, so it cannot be resolved without
         * knowing which file has been read.
         */
        public string $target,
        int $offset = 0,
    ) {
        parent::__construct(offset: $offset);
    }
}
