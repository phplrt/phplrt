<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast\Intermediate;

use Phplrt\Bench\PhpDocType\Ast\Node;

final readonly class ParameterListNode extends Node
{
    /**
     * @param list<Node> $parameters
     */
    public function __construct(
        public array $parameters,
    ) {}
}
