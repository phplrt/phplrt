<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests;

use Phplrt\Exception\Snippet\CapturedSourceLine;
use Phplrt\Exception\Snippet\SourceLine;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param iterable<mixed, SourceLine> $lines
     * @return list<string>
     */
    protected static function describe(iterable $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            $result[] = $line instanceof CapturedSourceLine
                ? \sprintf(
                    '>#%d@%d:%d-%d: %s',
                    $line->number,
                    $line->offset,
                    $line->startColumn,
                    $line->startColumn + $line->width,
                    $line->value,
                )
                : \sprintf(' #%d@%d: %s', $line->number, $line->offset, $line->value);
        }

        return $result;
    }

    /**
     * @param iterable<mixed, SourceLine> $lines
     */
    protected static function assertLinesMatchSource(string $source, iterable $lines): void
    {
        $expected = null;

        foreach ($lines as $line) {
            self::assertSame(
                \substr($source, $line->offset, \strlen($line->value)),
                $line->value,
                \sprintf('Line %d is expected to be located at offset %d of the source', $line->number, $line->offset),
            );

            if ($expected !== null) {
                self::assertSame($expected, $line->number, 'Lines are expected to be sequential');
            }

            $expected = $line->number + 1;
        }
    }
}
