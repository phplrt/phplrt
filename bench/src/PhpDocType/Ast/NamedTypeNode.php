<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast;

final readonly class NamedTypeNode extends Node
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public string $name,
    ) {}
}
