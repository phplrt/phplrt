<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

/**
 * Converts arbitrary values into the source objects.
 */
interface SourceFactoryInterface
{
    /**
     * Creates a source out of the given value.
     *
     * A value that is a source already MUST be given back as is.
     *
     * @template TArgSource
     * @param TArgSource $source the value to create a source out of
     * @return (TArgSource is ReadableInterface ? TArgSource : ReadableInterface)
     * @throws SourceExceptionInterface if a source cannot be created out of
     *         the given value
     */
    public function create(mixed $source): ReadableInterface;
}
