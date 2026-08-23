<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer\Renderer;

use Phplrt\Exception\Printer\Level;

/**
 * Prints the diagnostics as a plain text, without any decorations.
 */
final readonly class RawRustStyleRenderer extends RustStyleRenderer
{
    protected function printError(string $value, Level $level): string
    {
        return $value;
    }

    protected function printFrame(string $value): string
    {
        return $value;
    }

    protected function printDelimiter(): string
    {
        return '';
    }
}
