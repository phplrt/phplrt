<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

/**
 * The kind of declaration a symbol is declared by.
 *
 * Note: The `Type` suffix of the case names is due to `Class` being
 *       reserved for class name fetching.
 */
enum SymbolType: string
{
    case ClassType = 'class';

    case InterfaceType = 'interface';

    case TraitType = 'trait';

    case EnumType = 'enum';

    /**
     * Gets the kind of declaration the given symbol is declared by.
     *
     * @param non-empty-string $symbol
     */
    public static function createFromSymbol(string $symbol): self
    {
        // An enumeration is a class as well, so it is told apart before the
        // rest of them.
        return match (true) {
            \interface_exists($symbol) => self::InterfaceType,
            \enum_exists($symbol) => self::EnumType,
            \trait_exists($symbol) => self::TraitType,
            default => self::ClassType,
        };
    }
}
