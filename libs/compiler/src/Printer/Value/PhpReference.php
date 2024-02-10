<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer\Value;

abstract class PhpReference extends Reference
{
    protected function escape(string $identifier): string
    {
        return \trim($identifier, " \n\r\t\v\0\\");
    }
}
