<?php

declare(strict_types=1);

namespace Phplrt\Source\Provider;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceExceptionInterface;

interface SourceProviderInterface
{
    /**
     * @param mixed $source arbitrary source reference from which you can
     *        create a {@see ReadableInterface} instance
     *
     * @return ReadableInterface|null returns {@see null} in case of the object
     *         cannot be created
     * @throws SourceExceptionInterface in case of an error in creating the
     *         source object
     */
    public function create($source): ?ReadableInterface;
}
