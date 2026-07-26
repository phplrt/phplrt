<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Builder;

use Phplrt\Compiler\Parser\Definition\AlternationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\ConcatenationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\OptionalRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RepetitionRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RuleDefinition;
use Phplrt\Compiler\Parser\Definition\RuleReference;
use Phplrt\Compiler\Parser\Definition\TokenIdRuleDefinition;
use Phplrt\Compiler\Parser\Definition\TokenNameRuleDefinition;

/**
 * @internal this is an internal library trait, please do not use it in your code
 * @psalm-internal Phplrt\Compiler\Parser
 */
trait HasRuleDefinitions
{
    /**
     * @var \SplObjectStorage<RuleDefinition, null>
     */
    public private(set) \SplObjectStorage $rules {
        get => $this->rules ??= new \SplObjectStorage();
    }

    /**
     * Recognizes the token with the given identifier.
     *
     * @api
     *
     * @param non-empty-string|null $name
     */
    public function tokenId(int $tokenId, ?string $name = null): TokenIdRuleDefinition
    {
        $rule = new TokenIdRuleDefinition($tokenId, $name);

        $this->addRule($rule);

        return $rule;
    }

    /**
     * Recognizes the token with the given name.
     *
     * @api
     *
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
     * Refers to the rule with the given name.
     *
     * A reference is not a rule of the grammar, so it is not registered.
     *
     * @api
     *
     * @param non-empty-string $name
     */
    public function ref(string $name): RuleReference
    {
        return new RuleReference($name);
    }

    /**
     * Recognizes all of the given rules, one after another.
     *
     * @api
     *
     * @param list<RuleDefinition> $rules
     * @param non-empty-string|null $name
     */
    public function concatenation(array $rules = [], ?string $name = null): ConcatenationRuleDefinition
    {
        $rule = new ConcatenationRuleDefinition($rules, $name);

        $this->addRule($rule);

        return $rule;
    }

    /**
     * Recognizes the first of the given rules that matches the input.
     *
     * @api
     *
     * @param list<RuleDefinition> $rules
     * @param non-empty-string|null $name
     */
    public function alternation(array $rules = [], ?string $name = null): AlternationRuleDefinition
    {
        $rule = new AlternationRuleDefinition($rules, $name);

        $this->addRule($rule);

        return $rule;
    }

    /**
     * Recognizes the given rule, if the input matches it.
     *
     * @api
     *
     * @param non-empty-string|null $name
     */
    public function optional(RuleDefinition $rule, ?string $name = null): OptionalRuleDefinition
    {
        $definition = new OptionalRuleDefinition($rule, $name);

        $this->addRule($definition);

        return $definition;
    }

    /**
     * Recognizes the given rule as many times as the input matches it.
     *
     * @api
     *
     * @param int<0, max> $min
     * @param int<0, max>|float $max
     * @param non-empty-string|null $name
     */
    public function repetition(
        RuleDefinition $rule,
        int $min = 0,
        int|float $max = \INF,
        ?string $name = null,
    ): RepetitionRuleDefinition {
        $definition = new RepetitionRuleDefinition($rule, $min, $max, $name);

        $this->addRule($definition);

        return $definition;
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
