<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast\Intermediate;

use Phplrt\Bench\PhpDocType\Ast\Node;

final readonly class ClassConstantNameNode extends Node
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public string $name,
    ) {}
}
