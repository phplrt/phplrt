<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition;

final readonly class DefinitionContext
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $pathname,
        /**
         * @var int<0, max>
         */
        public int $offset,
    ) {}
}
