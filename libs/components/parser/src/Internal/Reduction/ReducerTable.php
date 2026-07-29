<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Reduction;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Context;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Grammar\SequenceInterface;

/**
 * Everything about a grammar that is known before the reduction starts.
 *
 * What a rule is built into does not depend on the source it has been
 * recognized in, so it is prepared once and shared by the reduction of every
 * source that follows.
 *
 * @phpstan-type ReducerType callable(Context, mixed): mixed
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
final readonly class ReducerTable
{
    /**
     * The rules reduced to the list of their children, indexed by the rule
     * identifiers.
     *
     * @var array<int, bool>
     */
    private array $merged;

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
        private array $reducers,
        /**
         * The identifier of the rule the analysis starts at.
         *
         * @var int<0, max>
         */
        private int $rule,
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
     * Returns the reduction of the traces of the given source.
     *
     * @param string $content the source code that has been read out of the
     *        source object
     */
    public function createReducer(ReadableInterface $source, string $content): TraceReducer
    {
        return new TraceReducer(
            reducers: $this->reducers,
            merged: $this->merged,
            rule: $this->rule,
            source: $source,
            content: $content,
        );
    }
}
