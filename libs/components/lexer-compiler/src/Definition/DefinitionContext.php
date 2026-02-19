<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Definition;

use Phplrt\Contracts\Source\ReadableInterface;

final readonly class DefinitionContext
{
    public function __construct(
        public ReadableInterface $source,
        /**
         * @var int<0, max>
         */
        public int $offset,
    ) {}
}
