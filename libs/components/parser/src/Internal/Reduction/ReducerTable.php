<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Reduction;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Context;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Grammar\SequenceInterface;

/**
 * Everything the reduction needs to know about a grammar, calculated once.
 *
 * What a rule gets built into doesn't depend on the source it was recognized in,
 * so it's prepared once here and then shared by every source that follows.
 *
 * @phpstan-type ReducerType callable(Context, mixed): mixed
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 *
 * @readonly
 */
final class ReducerTable
{
    /**
     * The rules reduced to the list of their children, indexed by the rule
     * identifiers.
     *
     * @var array<int, bool>
     */
    private readonly array $merged;

    /**
     * @param list<RuleInterface> $grammar
     */
    public function __construct(
        array $grammar,
        /**
         * The callbacks converting the rules into the nodes, indexed by the
         * rule identifiers. The rules without a callback are reduced to their
         * children
         *
         * @var array<int<0, max>, ReducerType>
         */
        private readonly array $reducers,
    ) {
        $this->merged = self::calculateMerged($grammar);
    }

    /**
     * @param list<RuleInterface> $grammar
     * @return array<int, bool>
     */
    private static function calculateMerged(array $grammar): array
    {
        $result = [];

        foreach ($grammar as $rule => $definition) {
            $result[$rule] = $definition instanceof SequenceInterface;
        }

        return $result;
    }

    /**
     * @param int<0, max> $rule the identifier of the rule the analysis has
     *        started at
     */
    public function createReducer(ReadableInterface $source, int $rule): TraceReducer
    {
        return new TraceReducer(
            reducers: $this->reducers,
            merged: $this->merged,
            rule: $rule,
            source: $source,
        );
    }
}
