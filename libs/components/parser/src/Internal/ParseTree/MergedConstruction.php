<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\ParseTree;

use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;

/**
 * TODO Move to compiler
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
final readonly class MergedConstruction
{
    /**
     * Returns the rules reduced to the list of their children instead of
     * a single value.
     *
     * @param list<RuleInterface> $grammar
     * @return array<int, bool>
     */
    public static function compute(array $grammar): array
    {
        $result = [];

        foreach ($grammar as $rule => $definition) {
            $result[$rule] = $definition instanceof Concatenation
                || $definition instanceof Repetition;
        }

        return $result;
    }
}
