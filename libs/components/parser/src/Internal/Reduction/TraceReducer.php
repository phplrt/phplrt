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
 * A trace is a flat list of three kinds of entry: a rule opens with its own
 * identifier, the tokens it reads follow, and it closes with its identifier
 * negated. The rules it contains are open and close in between, so the list reads
 * as a tree written down left to right.
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
     * The trace is walked from left to right, and every rule is built as it
     * closes. The values are held in a single list: a rule that closes takes
     * the tail of the list its own children have left there, is built out of
     * it, and leaves its own value in their place. Everything a rule needs to
     * know once it closes, therefore fits into two stacks, indexed by how deep
     * the rule is nested.
     *
     * Note: The walk is written as one loop of three branches, one per kind
     *       of entry, rather than as a method per kind, and the same
     *       {@see Context} describes every rule: a call and an object per
     *       entry are a call and an object per node of the tree, which is the
     *       one thing a reduction does often enough for it to matter. See
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
         * The place among the values each rule that is still open has started
         * at, indexed by the number of rules it is nested into.
         *
         * @var array<int, int> $starts
         */
        $starts = [];

        /**
         * The position each rule that is still open had reached when the rule
         * inside it opened, indexed the same way, or {@see null} for a rule that
         * had read nothing of its own by then.
         *
         * @var array<int, int<0, max>|null> $begins
         */
        $begins = [];
        $depth = 0;

        /**
         * The position the rule being read starts at, or {@see null} for a rule
         * that has read no token yet.
         *
         * @var int<0, max>|null $begin
         */
        $begin = null;

        /**
         * The last token the trace has read, which is where the source has
         * been read up to.
         */
        $token = null;

        for ($i = 0, $length = $trace->length; $i < $length; ++$i) {
            $entry = $entries[$i];

            // --- A token the rule being read has consumed ------------------

            if (!\is_int($entry)) {
                $values[$size++] = $token = $entry;
                // A rule starts at the first token it has read
                $begin ??= $entry->offset;

                continue;
            }

            // --- A rule opens: everything past this point is its own -------

            if ($entry >= 0) {
                $starts[$depth] = $size;
                $begins[$depth] = $begin;
                ++$depth;

                $begin = null;

                continue;
            }

            // --- A rule closes: it is built out of what it has left behind -

            // A rule closes with its own identifier negated
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

                $context->rule = $rule;                     // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
                // A rule that has read nothing is empty at the position
                // the reading has reached
                $context->begin = $first ?? $to;            // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
                $context->length = $span > 0 ? $span : 0;   // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass

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
