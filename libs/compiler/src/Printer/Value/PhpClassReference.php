<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer\Value;

use Phplrt\Compiler\Printer\PrinterInterface;

final class PhpClassReference extends PhpReference
{
    public function print(PrinterInterface $printer): string
    {
        if ($this->alias !== null) {
            return \vsprintf('use %s as %s;', [
                $this->escape($this->reference),
                $this->escape($this->alias),
            ]);
        }

        return \vsprintf('use %s;', [
            $this->escape($this->reference),
        ]);
    }
}
