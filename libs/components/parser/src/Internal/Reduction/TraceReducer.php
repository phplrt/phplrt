<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Reduction;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Context;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Grammar\SequenceInterface;
use Phplrt\Parser\Internal\Tracing\Result\TracingResult;

/**
 * Builds a trace into whatever the grammar describes.
 *
 * The trace is read from left to right: a rule opens with its identifier,
 * closes with the identifier negated, and everything in between belongs to
 * it. A rule that closes is built out of the values collected since it was
 * opened, so the values of the whole tree are held in a single list and each
 * rule takes the tail that is its own.
 *
 * What a rule gets built into does not depend on the source it was recognized
 * in, so it is worked out once and then used for every source that follows.
 *
 * @phpstan-type ReducerType callable(Context, mixed): mixed
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
final class TraceReducer
{
    /**
     * The rules built out of the values of all their children, rather than
     * out of a single value, indexed by the rule identifiers.
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
        private readonly array $reducers = [],
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
     * Builds the given trace of the given source.
     *
     * Note: Every rule of a single trace is described by the same
     *       {@see Context}, filled in anew before each reducer is called: a
     *       context per rule would be an object per node of the tree, and the
     *       nodes are what a reduction is made of. The context therefore
     *       describes the rule being reduced and nothing else — see
     *       {@see Context} on how long it may be kept.
     *
     * @param int<0, max> $initial the identifier of the rule the analysis has
     *        started at
     */
    public function reduce(TracingResult $trace, ReadableInterface $source, int $initial): mixed
    {
        $entries = $trace->entries;

        $reducers = $this->reducers;
        $merged = $this->merged;
        $context = new Context($initial, $source);

        /**
         * The values built so far, the ones of a rule following the ones of
         * the rule it belongs to.
         *
         * @var array<int, mixed> $values
         */
        $values = [];
        $size = 0;

        /**
         * The place among the values each rule being read has started at,
         * indexed by the number of rules it is nested into.
         *
         * @var array<int, int> $starts
         */
        $starts = [];

        /**
         * The position each rule being read has started at, indexed the same
         * way, or "null" for a rule that has read nothing yet.
         *
         * @var array<int, int<0, max>|null> $begins
         */
        $begins = [];
        $depth = 0;

        $begin = null;
        $token = null;

        for ($i = 0, $length = $trace->length; $i < $length; ++$i) {
            $entry = $entries[$i];

            if (!\is_int($entry)) {
                $values[$size++] = $token = $entry;
                // A rule starts at the first token it has read
                $begin ??= $entry->offset;

                continue;
            }

            if ($entry >= 0) {
                $starts[$depth] = $size;
                $begins[$depth] = $begin;
                ++$depth;

                $begin = null;

                continue;
            }

            $rule = -$entry - 1;
            $first = $begin;

            // Note: The rule this one belongs to has read everything this one
            //       has, so it starts at the very same position unless it has
            //       read something of its own before
            $begin = $begins[--$depth] ?? $first;
            $start = $starts[$depth];

            $result = $merged[$rule]
                ? self::merge($values, $start, $size)
                // Any other rule contains a single value, which is passed
                // through as is
                : ($size > $start ? $values[$start] : []);

            $size = $start;
            $reducer = $reducers[$rule] ?? null;

            if ($reducer !== null) {
                // A source nothing has been read of is empty at its beginning
                $to = $token === null ? 0 : $token->offset + $token->size;
                $span = $first === null ? 0 : $to - $first;

                $context->rule = $rule;                 // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
                // A rule that has read nothing is empty at the position the
                // reading has reached
                $context->begin = $first ?? $to;        // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
                $context->length = $span > 0 ? $span : 0;    // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass

                $result = $reducer($context, $result) ?? $result;
            }

            $values[$size++] = $result;
        }

        return $values[0] ?? [];
    }

    /**
     * Returns the values of a sequence as a single list, the ones of a nested
     * sequence among them.
     *
     * @param array<int, mixed> $values
     * @return list<mixed>
     */
    private static function merge(array $values, int $from, int $to): array
    {
        $result = [];

        for ($i = $from; $i < $to; ++$i) {
            $value = $values[$i];

            if (!\is_array($value)) {
                $result[] = $value;

                continue;
            }

            foreach ($value as $nested) {
                $result[] = $nested;
            }
        }

        return $result;
    }
}
