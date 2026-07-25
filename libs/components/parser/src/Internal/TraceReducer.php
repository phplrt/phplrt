<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Context;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\Tracing\Result\Success;

/**
 * Reduces the recognized rules into the abstract syntax tree.
 *
 * @phpstan-type ReducerType callable(Context, mixed): mixed
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
final readonly class TraceReducer
{
    public function __construct(
        /**
         * @var list<RuleInterface>
         */
        private array $grammar = [],
        /**
         * The callbacks converting the rules into the nodes, indexed by the
         * rule identifiers. The rules without a callback are reduced to their
         * children
         *
         * @var array<int<0, max>, ReducerType>
         */
        private array $reducers = [],
    ) {}

    public function reduce(Success $trace, string $source): mixed
    {
        $types = $trace->types;
        $references = $trace->references;

        $children = [];
        $stack = [];
        $token = null;

        for ($i = 0; $i < $trace->length; ++$i) {
            $reference = $references[$i];

            switch ($types[$i]) {
                case Success::TYPE_ENTER:
                    $stack[] = $children;
                    $children = [];
                    break;

                case Success::TYPE_TOKEN:
                    if ($reference instanceof TokenInterface) {
                        $children[] = $token = $reference;
                    }
                    break;

                case Success::TYPE_LEAVE:
                    if (!\is_int($reference)) {
                        break;
                    }

                    $result = $this->fold($reference, $children);

                    $children = \array_pop($stack) ?? [];
                    $children[] = $this->build($reference, $source, $token, $result);
                    break;
            }
        }

        return $children[0] ?? [];
    }

    /**
     * Joins the values of the nested rules in the way the rule requires.
     *
     * @param list<mixed> $children
     */
    private function fold(int $rule, array $children): mixed
    {
        $definition = $this->grammar[$rule] ?? null;

        if ($definition instanceof Concatenation || $definition instanceof Repetition) {
            return $this->merge($children);
        }

        // Any other rule contains a single value, which is passed through as is
        return $children[0] ?? [];
    }

    /**
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

    private function build(int $rule, string $source, ?TokenInterface $token, mixed $children): mixed
    {
        $reducer = $this->reducers[$rule] ?? null;

        if ($reducer === null) {
            return $children;
        }

        $context = new Context(\max(0, $rule), $source, $token);

        return $reducer($context, $children) ?? $children;
    }
}
