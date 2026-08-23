<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

/**
 * A symbol the generated code loads before it is referred to.
 */
final readonly class SymbolInclude implements \Stringable
{
    public function __construct(
        /**
         * The fully qualified name of the symbol.
         *
         * @var non-empty-string
         */
        public string $symbol,
        /**
         * The kind of declaration the symbol is declared by.
         */
        public SymbolType $type = SymbolType::ClassType,
    ) {}

    public function __toString(): string
    {
        return $this->symbol;
    }
}
