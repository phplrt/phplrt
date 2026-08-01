<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast;

final readonly class CallableParameterNode extends Node
{
    public function __construct(
        public Node $type,
        public bool $byReference = false,
        public bool $variadic = false,
        public ?string $name = null,
        public bool $optional = false,
    ) {}
}
