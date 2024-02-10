<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer\Value;

use Phplrt\Compiler\Printer\PrintableValueInterface;
use Phplrt\Compiler\Printer\PrinterInterface;

final class RawValue implements PrintableValueInterface
{
    /**
     * @readonly
     */
    private string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function print(PrinterInterface $printer): string
    {
        return $this->code;
    }
}
