<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer;

/**
 * Responsible for printing values that require specific formatting.
 */
interface PrintableValueInterface
{
    /**
     * Converts a complex/specific value to a string.
     */
    public function print(PrinterInterface $printer): string;
}
