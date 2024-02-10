<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer\Value;

use Phplrt\Compiler\Printer\PrinterInterface;

final class PhpConstReference extends PhpReference
{
    public function print(PrinterInterface $printer): string
    {
        if ($this->alias !== null) {
            return \vsprintf('use const %s as %s;', [
                $this->escape($this->reference),
                $this->escape($this->alias),
            ]);
        }

        return \vsprintf('use const %s;', [
            $this->escape($this->reference),
        ]);
    }
}
