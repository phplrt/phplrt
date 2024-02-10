<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer;

abstract class Printer implements PrinterInterface
{
    /**
     * @readonly
     * @psalm-readonly-allow-private-mutation
     */
    protected Style $style;

    public function __construct(Style $style = null)
    {
        $this->style = $style ?? new Style();
    }

    public function getStyle(): Style
    {
        return $this->style;
    }
}
