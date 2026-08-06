<?php

declare(strict_types=1);

namespace Phplrt\Source\Driver;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Creates a source out of a reference of one particular kind.
 */
interface SourceDriverInterface
{
    /**
     * @param mixed $source arbitrary reference the source is created from
     * @return ReadableInterface|null returns {@see null} in case the reference
     *         is of a kind this driver does not recognize
     * @throws SourceExceptionInterface in case the reference is recognized,
     *         but the source cannot be created from it
     */
    public function tryCreate(mixed $source): ?ReadableInterface;
}
