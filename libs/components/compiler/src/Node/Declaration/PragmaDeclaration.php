<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Declaration;

/**
 * Configures the compilation of the grammar.
 *
 * @readonly
 */
final class PragmaDeclaration extends Declaration
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * The name of the setting being changed.
         *
         * Which names are recognized is decided while the grammar is compiled,
         * so an unknown one is still readable here.
         *
         * @var non-empty-string
         */
        public readonly string $name,
        /**
         * The value of the setting, as it is written.
         *
         * @var non-empty-string
         */
        public readonly string $value,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            offset: $offset,
            length: $length,
        );
    }
}
