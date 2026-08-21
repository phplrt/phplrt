<?php

declare(strict_types=1);

namespace Phplrt\Source\Driver;

use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\ResourceSource;

/**
 * Creates a source out of an open resource.
 */
final readonly class ResourceSourceDriver implements SourceDriverInterface
{
    /**
     * @throws NotCreatableException in case the resource is not a stream
     */
    public function tryCreate(mixed $source): ?ResourceSource
    {
        if (!\is_resource($source)) {
            return null;
        }

        return new ResourceSource($source);
    }
}
