<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast\Intermediate;

use Phplrt\Bench\PhpDocType\Ast\Node;

/**
 * The "<...>" of a generic type, before the type it belongs to is known.
 */
final readonly class GenericArgumentsNode extends Node
{
    /**
     * @param list<Node> $arguments
     */
    public function __construct(
        public array $arguments,
    ) {}
}
