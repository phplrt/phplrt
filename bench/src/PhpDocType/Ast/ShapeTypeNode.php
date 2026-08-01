<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast;

final readonly class ShapeTypeNode extends Node
{
    /**
     * @param list<ShapeItemNode> $items
     */
    public function __construct(
        public Node $type,
        public array $items,
    ) {}
}
