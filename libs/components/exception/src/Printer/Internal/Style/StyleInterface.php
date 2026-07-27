<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer\Internal\Style;

use Phplrt\Exception\Printer\Level;

/**
 * Decorates the parts of the output.
 *
 * @internal this is an internal library interface, please do not use it in your code
 * @psalm-internal Phplrt\Exception
 */
interface StyleInterface
{
    /**
     * Decorates the value describing the error of the given severity.
     */
    public function paint(string $value, Level $level): string;

    /**
     * Decorates the value of a lower importance than the source code around it.
     */
    public function dim(string $value): string;

    /**
     * Decorates the frame around the source code: the numbers of the lines,
     * the gutter separating them and the arrow pointing at the location of
     * the error.
     */
    public function frame(string $value): string;

    /**
     * Returns the visible representation of the line delimiter or an empty
     * string in case it should not be printed.
     */
    public function getDelimiter(): string;
}
