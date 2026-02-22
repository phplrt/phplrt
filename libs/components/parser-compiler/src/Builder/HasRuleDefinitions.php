<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Builder;

use Phplrt\Compiler\Parser\Definition\RuleDefinition;
use Phplrt\Compiler\Parser\Definition\TokenIdRuleDefinition;
use Phplrt\Compiler\Parser\Definition\TokenNameRuleDefinition;

/**
 * @internal this is an internal library trait, please do not use it in your code
 * @psalm-internal Phplrt\Compiler\Parser
 */
trait HasRuleDefinitions
{
    /**
     * @var iterable<array-key, RuleDefinition>
     */
    public private(set) \SplObjectStorage $rules {
        get => $this->rules ??= new \SplObjectStorage();
    }

    /**
     * @param non-empty-string|null $name
     */
    public function tokenId(int $tokenId, ?string $name = null): TokenIdRuleDefinition
    {
        $rule = new TokenIdRuleDefinition($tokenId, $name);

        $this->addRule($rule);

        return $rule;
    }

    /**
     * @param non-empty-string $tokenName
     * @param non-empty-string|null $name
     */
    public function tokenName(string $tokenName, ?string $name = null): TokenNameRuleDefinition
    {
        $rule = new TokenNameRuleDefinition($tokenName, $name);

        $this->addRule($rule);

        return $rule;
    }

    /**
     * @return $this
     */
    public function addRule(RuleDefinition $definition): self
    {
        $this->rules->offsetSet($definition);

        return $this;
    }

    /**
     * @return $this
     */
    public function removeRule(RuleDefinition $definition): self
    {
        $this->rules->offsetUnset($definition);

        return $this;
    }
}
