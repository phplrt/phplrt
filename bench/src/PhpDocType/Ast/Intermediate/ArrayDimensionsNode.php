<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast\Intermediate;

use Phplrt\Bench\PhpDocType\Ast\Node;

final readonly class ArrayDimensionsNode extends Node
{
    /**
     * @param int<1, max> $count
     */
    public function __construct(
        public int $count,
    ) {}
}
