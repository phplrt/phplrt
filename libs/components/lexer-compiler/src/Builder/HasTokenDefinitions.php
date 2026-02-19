<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Builder;

use Phplrt\Compiler\Lexer\Definition\RegexTokenDefinition;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\Definition\ValueTokenDefinition;

trait HasTokenDefinitions
{
    /**
     * @var array<array-key, TokenDefinition>
     */
    public private(set) array $tokens = [];

    /**
     * @param non-empty-string $pattern
     * @param non-empty-string|null $name
     */
    public function match(string $pattern, ?string $name = null): RegexTokenDefinition
    {
        return $this->tokens[] = new RegexTokenDefinition($pattern, $name);
    }

    /**
     * @param non-empty-string $value
     * @param non-empty-string|null $name
     */
    public function value(string $value, ?string $name = null): ValueTokenDefinition
    {
        return $this->tokens[] = new ValueTokenDefinition($value, $name);
    }

    /**
     * @return $this
     */
    public function addTokenDefinition(TokenDefinition $definition): self
    {
        $this->removeTokenDefinition($definition);

        $this->tokens[] = $definition;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeTokenDefinition(TokenDefinition $definition): self
    {
        foreach ($this->tokens as $index => $token) {
            if ($token === $definition) {
                unset($this->tokens[$index]);

                break;
            }
        }

        return $this;
    }
}
