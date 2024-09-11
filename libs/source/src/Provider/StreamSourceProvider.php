<?php

declare(strict_types=1);

namespace Phplrt\Source\Provider;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\SourceFactory;

final class StreamSourceProvider implements SourceProviderInterface
{
    public function __construct(
        private readonly SourceFactory $parent,
    ) {}

    public function create(mixed $source): ?ReadableInterface
    {
        if (!\is_resource($source)) {
            return null;
        }

        return $this->parent->createFromStream($source);
    }
}
