<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal;

/**
 * Tells the names of the tokens that could have been read instead of the one
 * an error occurred on.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
final class ExpectationPrinter
{
    /**
     * The number of the names an error tells apart, the rest of them being
     * counted instead.
     *
     * @var int<1, max>
     */
    private const int MAX_LISTED = 3;

    /**
     * Returns the first few of the given names, along with the number of the
     * ones left out.
     *
     * @param list<non-empty-string> $expected
     */
    public static function printShort(array $expected): string
    {
        $hidden = \count($expected) - self::MAX_LISTED;

        if ($hidden <= 0) {
            return self::printAll($expected);
        }

        // The names left out belong to the same choice, so the ones told apart
        // are not closed off by a conjunction
        return \sprintf(
            '%s (+%d more)',
            \implode(', ', \array_slice($expected, 0, self::MAX_LISTED)),
            $hidden,
        );
    }

    /**
     * Returns all the given names, the last of them told apart from the rest
     * the way a sentence tells it.
     *
     * @param list<non-empty-string> $expected
     */
    public static function printAll(array $expected): string
    {
        $last = \array_pop($expected);

        if ($last === null) {
            return '';
        }

        if ($expected === []) {
            return $last;
        }

        return \sprintf('%s or %s', \implode(', ', $expected), $last);
    }
}
