<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\ParseTree;

use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\TraceReducer;

/**
 * TODO Move to compiler
 *
 * @phpstan-import-type ReducerType from TraceReducer
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
final readonly class KeptConstruction
{
    /**
     * Returns the rules that must be present in the resulting tree.
     *
     * @param list<RuleInterface> $grammar
     * @param array<int<0, max>, ReducerType> $reducers
     * @return array<int, bool>
     */
    public static function compute(array $grammar, array $reducers): array
    {
        $result = [];

        foreach ($grammar as $rule => $definition) {
            // A rule containing a single value passes it through as is, so
            // without a reducer it adds nothing to the tree
            $result[$rule] = isset($reducers[$rule])
                || !($definition instanceof Alternation || $definition instanceof Optional);
        }

        return $result;
    }
}
