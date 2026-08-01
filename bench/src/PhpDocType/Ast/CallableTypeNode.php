<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast;

final readonly class CallableTypeNode extends Node
{
    /**
     * @param list<Node> $template
     * @param list<CallableParameterNode> $parameters
     */
    public function __construct(
        public Node $type,
        public array $template,
        public array $parameters,
        public Node $return,
    ) {}
}
