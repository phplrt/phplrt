<?php

declare(strict_types=1);

namespace Phplrt\Source\Driver;

use Phplrt\Source\Source;

/**
 * Creates a source out of a string containing the source code itself.
 */
final readonly class StringSourceDriver implements SourceDriverInterface
{
    public function tryCreate(mixed $source): ?Source
    {
        if (!\is_string($source)) {
            return null;
        }

        return new Source($source);
    }
}
