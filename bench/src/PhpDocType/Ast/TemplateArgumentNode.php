<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType\Ast;

final readonly class TemplateArgumentNode extends Node
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public string $name,
        public ?Node $bound = null,
        public ?Node $super = null,
        public ?Node $default = null,
    ) {}
}
