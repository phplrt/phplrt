<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

interface SourceFactoryInterface
{
    /**
     * @param mixed $source Arbitrary source reference from which you can
     *        create a {@see ReadableInterface} instance.
     *
     * @throws SourceExceptionInterface In case of an error in creating the
     *         source object.
     */
    public function create($source): ReadableInterface;
}
