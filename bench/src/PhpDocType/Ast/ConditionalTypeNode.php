<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast;

final readonly class ConditionalTypeNode extends Node
{
    public function __construct(
        public ?Node $subject,
        public Node $target,
        public Node $then,
        public Node $else,
        public bool $negated = false,
    ) {}

    public function withSubject(Node $subject): self
    {
        return new self($subject, $this->target, $this->then, $this->else, $this->negated);
    }
}
