<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast;

final readonly class GenericTypeNode extends Node
{
    /**
     * @param list<Node> $arguments
     */
    public function __construct(
        public Node $type,
        public array $arguments,
    ) {}
}
