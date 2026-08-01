<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast\Intermediate;

use Phplrt\Bench\PhpDocType\Ast\Node;

final readonly class ShapeItemsNode extends Node
{
    /**
     * @param list<Node> $items
     */
    public function __construct(
        public array $items,
    ) {}
}
