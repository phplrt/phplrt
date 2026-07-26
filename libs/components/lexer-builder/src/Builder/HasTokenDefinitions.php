<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Builder;

use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Definition\ValueTokenDefinition;

/**
 * @internal this is an internal library trait, please do not use it in your code
 * @psalm-internal Phplrt\Lexer\Builder
 */
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
    public function addPattern(string $pattern, ?string $name = null): RegexTokenDefinition
    {
        $definition = new RegexTokenDefinition($pattern, $name);

        $this->addToken($definition);

        return $definition;
    }

    /**
     * @param non-empty-string $value
     * @param non-empty-string|null $name
     */
    public function addValue(string $value, ?string $name = null): ValueTokenDefinition
    {
        $definition = new ValueTokenDefinition($value, $name);

        $this->addToken($definition);

        return $definition;
    }

    /**
     * @return $this
     */
    public function addToken(TokenDefinition $definition): self
    {
        $this->removeToken($definition);

        $this->tokens[] = $definition;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeToken(TokenDefinition $definition): self
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
