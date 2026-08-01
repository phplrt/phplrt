<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast;

final readonly class ShapeItemNode extends Node
{
    public function __construct(
        public ?string $key,
        public bool $optional,
        public Node $value,
    ) {}
}
