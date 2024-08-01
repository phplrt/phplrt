<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

interface SourceFactoryInterface
{
    /**
     * @param mixed $source arbitrary source reference from which you can
     *        create a {@see ReadableInterface} instance
     *
     * @throws SourceExceptionInterface in case of an error in creating the
     *         source object
     */
    public function create(mixed $source): ReadableInterface;
}
