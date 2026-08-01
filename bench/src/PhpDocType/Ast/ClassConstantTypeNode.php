<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast;

final readonly class ClassConstantTypeNode extends Node
{
    /**
     * @param non-empty-string $constant
     */
    public function __construct(
        public Node $class,
        public string $constant,
    ) {}
}
