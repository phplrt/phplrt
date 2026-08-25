<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Reduction;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Context;
use Phplrt\Parser\Internal\Tracing\Result\TracingResult;

/**
 * Builds the traces of a single source into whatever the grammar describes.
 *
 * @phpstan-import-type ReducerType from ReducerTable
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
final readonly class TraceReducer
{
    private Context $context;

    /**
     * @param int<0, max> $rule
     */
    public function __construct(
        /**
         * The callbacks converting the rules into the nodes, indexed by the
         * rule identifiers. The rules without a callback are reduced to their
         * children
         *
         * @var array<int<0, max>, ReducerType>
         */
        private array $reducers,
        /**
         * The rules reduced to the list of their children, indexed by the rule
         * identifiers
         *
         * @var array<int, bool>
         */
        private array $merged,
        int $rule,
        ReadableInterface $source,
    ) {
        $this->context = new Context($rule, $source);
    }

    public function reduce(TracingResult $trace): mixed
    {
        $entries = $trace->entries;

        $reducers = $this->reducers;
        $merged = $this->merged;
        $prototype = $this->context;

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
                /**
                 * Clone optimization: speeds up the creation of a new object:
                 * faster than instantiation.
                 */
                $context = clone $prototype;
                $context->rule = $rule;                 // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass

                if ($token !== null) {
                    $to = $token->offset + $token->size;

                    // A rule that has read nothing is empty at the position
                    // the reading has reached
                    $context->begin = $first ?? $to;    // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass

                    $span = $first === null ? 0 : $to - $first;

                    if ($span > 0) {
                        $context->length = $span;       // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
                    }
                }

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
