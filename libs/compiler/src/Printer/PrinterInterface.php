<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer;

interface PrinterInterface
{
    /**
     * Converts given payload to string representation.
     */
    public function print(mixed $data, bool $multiline = true): string;

    /**
     * Returns printer style definition.
     */
    public function getStyle(): Style;
}
