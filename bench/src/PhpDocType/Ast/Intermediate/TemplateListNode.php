<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast\Intermediate;

use Phplrt\Bench\PhpDocType\Ast\Node;

final readonly class TemplateListNode extends Node
{
    /**
     * @param list<Node> $arguments
     */
    public function __construct(
        public array $arguments,
    ) {}
}
