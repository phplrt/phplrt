<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

/**
 * A symbol the generated code loads before it is referred to.
 *
 * @readonly
 */
final class SymbolInclude implements \Stringable
{
    public function __construct(
        /**
         * The fully qualified name of the symbol.
         *
         * @var non-empty-string
         */
        public readonly string $symbol,
        /**
         * The kind of declaration the symbol is declared by.
         */
        public readonly SymbolType $type = SymbolType::ClassType,
    ) {}

    public function __toString(): string
    {
        return $this->symbol;
    }
}
