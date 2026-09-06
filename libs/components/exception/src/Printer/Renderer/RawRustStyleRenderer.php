<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer\Renderer;

use Phplrt\Exception\Analysis\FailureLevel;

/**
 * Prints the diagnostics as a plain text, without any decorations.
 *
 * @readonly
 */
final class RawRustStyleRenderer extends RustStyleRenderer
{
    protected function printError(string $value, FailureLevel $level): string
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
