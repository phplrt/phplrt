<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Declaration;

/**
 * Configures the compilation of the grammar.
 *
 * For example,
 * ```
 * %pragma root Type
 * ```
 */
final readonly class PragmaDeclaration extends Declaration
{
    /**
     * @param non-empty-string $name
     * @param non-empty-string $value
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The name of the setting being changed.
         *
         * Which names are recognized is decided while the grammar is compiled,
         * so an unknown one is still readable here.
         */
        public string $name,
        /**
         * The value of the setting, as it is written.
         */
        public string $value,
        int $offset = 0,
    ) {
        parent::__construct(offset: $offset);
    }
}
