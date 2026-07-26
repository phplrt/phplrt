<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Parser\Builder\Definition\RuleDefinition;

/**
 * Tells which rules of the grammar refer to a given one.
 *
 * The grammar is described from the top down, so a rule knows its children, but
 * not the other way round.
 */
final readonly class RuleParents
{
    /**
     * @param \SplObjectStorage<RuleDefinition, list<RuleDefinition>> $parents
     */
    private function __construct(
        private \SplObjectStorage $parents,
    ) {}

    /**
     * @param list<RuleDefinition> $rules
     */
    public static function createFromRules(array $rules): self
    {
        /** @var \SplObjectStorage<RuleDefinition, list<RuleDefinition>> $parents */
        $parents = new \SplObjectStorage();

        foreach ($rules as $rule) {
            foreach ($rule->children as $child) {
                $result = $parents[$child] ?? [];

                $result[] = $rule;

                $parents[$child] = $result;
            }
        }

        return new self($parents);
    }

    /**
     * @return list<RuleDefinition>
     */
    public function findParents(RuleDefinition $rule): array
    {
        return $this->parents[$rule] ?? [];
    }

    /**
     * Returns the only rule referring to the given one, or {@see null} in case
     * of it is referred to from several places or from none.
     */
    public function findSingleParent(RuleDefinition $rule): ?RuleDefinition
    {
        $parents = $this->findParents($rule);

        if (\count($parents) !== 1) {
            return null;
        }

        return $parents[0];
    }
}
