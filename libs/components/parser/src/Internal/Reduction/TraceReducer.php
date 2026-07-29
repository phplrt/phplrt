<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Reduction;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Context;
use Phplrt\Parser\Internal\Tracing\Result\Success;

/**
 * Builds the traces of a single source into the value the grammar describes.
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
        string $content,
    ) {
        $this->context = new Context(
            rule: $rule,
            source: $source,
            content: $content,
            token: null,
        );
    }

    public function reduce(Success $trace): mixed
    {
        $entries = $trace->entries;

        $reducers = $this->reducers;
        $merged = $this->merged;
        $prototype = $this->context;

        $children = [];
        $stack = [];
        $token = null;

        for ($i = 0, $length = $trace->length; $i < $length; ++$i) {
            $entry = $entries[$i];

            if (!\is_int($entry)) {
                $children[] = $token = $entry;

                continue;
            }

            if ($entry >= 0) {
                $stack[] = $children;
                $children = [];

                continue;
            }

            $rule = -$entry - 1;

            if ($merged[$rule]) {
                $result = $children;

                if (isset($children[1])) {
                    foreach ($children as $child) {
                        if (\is_array($child)) {
                            $result = $this->merge($children);

                            break;
                        }
                    }
                } elseif (isset($children[0]) && \is_array($children[0])) {
                    $result = $children[0];
                }
            } else {
                // Any other rule contains a single value, which is passed
                // through as is
                $result = $children[0] ?? [];
            }

            $children = \array_pop($stack) ?? [];
            $reducer = $reducers[$rule] ?? null;

            if ($reducer === null) {
                $children[] = $result;

                continue;
            }

            /**
             * Clone optimization: speeds up the creation of a new object:
             * faster than instantiation.
             */
            $context = clone $prototype;

            $context->rule = $rule;     // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
            $context->token = $token;   // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass

            $children[] = $reducer($context, $result) ?? $result;
        }

        return $children[0] ?? [];
    }

    /**
     * Unwraps the children of the nested rules into a single list.
     *
     * @param list<mixed> $children
     * @return list<mixed>
     */
    private function merge(array $children): array
    {
        $result = [];

        foreach ($children as $child) {
            if (!\is_array($child)) {
                $result[] = $child;

                continue;
            }

            foreach ($child as $value) {
                $result[] = $value;
            }
        }

        return $result;
    }
}
