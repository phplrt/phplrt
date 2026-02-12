<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Generator;

final readonly class Phplrt4GeneratedResult extends GeneratedResult
{
    /**
     * @param non-empty-string $result
     */
    public function __construct(
        string $result,
        /**
         * @var non-empty-string
         */
        public string $pattern,
        /**
         * @var array<non-empty-string, non-empty-string>
         */
        public array $channels = [],
        /**
         * @var array<non-empty-string, non-empty-string|int>
         */
        public array $aliases = [],
    ) {
        parent::__construct($result);
    }
}
