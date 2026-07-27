<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer\Internal\Style;

use Phplrt\Exception\Printer\Level;

/**
 * Prints the output as is, without any decorations.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Exception
 */
final readonly class PlainStyle implements StyleInterface
{
    public function paint(string $value, Level $level): string
    {
        return $value;
    }

    public function dim(string $value): string
    {
        return $value;
    }

    public function frame(string $value): string
    {
        return $value;
    }

    public function getDelimiter(): string
    {
        return '';
    }
}
