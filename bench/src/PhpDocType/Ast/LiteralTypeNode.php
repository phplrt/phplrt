<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast;

final readonly class LiteralTypeNode extends Node
{
    public function __construct(
        public string|int|float $value,
    ) {}
}
