<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Definition;

/**
 * @template TDefinition of TokenDefinition = TokenDefinition
 */
final readonly class AliasedDefinition implements \Stringable
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $alias,
        /**
         * @var TDefinition
         */
        public TokenDefinition $definition,
    ) {}

    public function __toString(): string
    {
        return (string) $this->definition;
    }
}
