<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests;

use Phplrt\Exception\Snippet\CapturedSourceLine;
use Testo\Assert;

abstract class TestCase
{
    protected static function describe(iterable $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            $result[] = $line instanceof CapturedSourceLine
                ? \sprintf(
                    '>#%d@%d:%d-%d: %s',
                    $line->number,
                    $line->offset,
                    $line->captured->offset,
                    $line->captured->endsAt,
                    $line->value,
                )
                : \sprintf(' #%d@%d: %s', $line->number, $line->offset, $line->value);
        }

        return $result;
    }

    protected static function assertLinesMatchSource(string $source, iterable $lines): void
    {
        $expected = null;

        foreach ($lines as $line) {
            Assert::same($line->value, \substr($source, $line->offset, \strlen($line->value)), \sprintf('Line %d is expected to be located at offset %d of the source', $line->number, $line->offset));

            if ($expected !== null) {
                Assert::same($line->number, $expected, 'Lines are expected to be sequential');
            }

            $expected = $line->number + 1;
        }
    }
}
