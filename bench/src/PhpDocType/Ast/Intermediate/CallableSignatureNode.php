<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast\Intermediate;

use Phplrt\Bench\PhpDocType\Ast\Node;

final readonly class CallableSignatureNode extends Node
{
    /**
     * @param list<Node> $template
     * @param list<Node> $parameters
     */
    public function __construct(
        public array $template,
        public array $parameters,
        public Node $return,
    ) {}
}
