<?php

declare(strict_types=1);

namespace Phplrt\Source\Provider;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\SourceFactory;

final class TextSourceProvider implements SourceProviderInterface
{
    public function __construct(
        /**
         * @readonly
         */
        private SourceFactory $parent
    ) {}

    public function create(mixed $source): ?ReadableInterface
    {
        if (!\is_string($source)) {
            return null;
        }

        return $this->parent->createFromString($source);
    }
}
