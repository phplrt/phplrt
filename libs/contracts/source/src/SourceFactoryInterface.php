<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

interface SourceFactoryInterface
{
    /**
     * @throws SourceExceptionInterface in case of an error in creating the source object
     */
    public function create(mixed $source): ReadableInterface;
}
