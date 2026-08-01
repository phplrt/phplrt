<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast;

final readonly class IntersectionTypeNode extends Node
{
    /**
     * @param list<Node> $types
     */
    public function __construct(
        public array $types,
    ) {}
}
