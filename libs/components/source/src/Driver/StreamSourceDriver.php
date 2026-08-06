<?php

declare(strict_types=1);

namespace Phplrt\Source\Driver;

use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Stream;

/**
 * Creates a source out of an open resource.
 */
final readonly class StreamSourceDriver implements SourceDriverInterface
{
    /**
     * @throws NotCreatableException in case the resource is not a stream
     */
    public function tryCreate(mixed $source): ?Stream
    {
        if (!\is_resource($source)) {
            return null;
        }

        return new Stream($source);
    }
}
