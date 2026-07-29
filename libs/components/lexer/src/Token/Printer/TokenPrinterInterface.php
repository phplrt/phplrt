<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token\Printer;

use Phplrt\Contracts\Lexer\TokenInterface;

interface TokenPrinterInterface
{
    public function print(TokenInterface $token): string;
}
