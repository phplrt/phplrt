<?php

declare(strict_types=1);

namespace Phplrt\Source\Provider;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\SourceFactory;

final class SplFileInfoSourceProvider implements SourceProviderInterface
{
    public function __construct(
        /**
         * @readonly
         */
        private SourceFactory $parent
    ) {}

    public function create(mixed $source): ?ReadableInterface
    {
        if (!$source instanceof \SplFileInfo) {
            return null;
        }

        return $this->parent->createFromFile($source->getPathname());
    }
}
