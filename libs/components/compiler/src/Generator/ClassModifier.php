<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

/**
 * The way the class of a generated parser is declared.
 */
enum ClassModifier: string
{
    /**
     * The parser is declared as an ordinary class.
     */
    case Default = '';

    /**
     * The parser is the base of a class written by hand and cannot be
     * instantiated on its own.
     */
    case Abstract = 'abstract';

    /**
     * The parser is the whole of what it is and cannot be extended.
     */
    case Final = 'final';
}
