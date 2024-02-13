<?php

declare(strict_types=1);

namespace Phplrt\Source\Provider;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\SourceFactory;

final class StreamSourceProvider implements SourceProviderInterface
{
    /**
     * @readonly
     */
    private SourceFactory $parent;

    public function __construct(SourceFactory $parent)
    {
        $this->parent = $parent;
    }

    public function create($source): ?ReadableInterface
    {
        if (!\is_resource($source)) {
            return null;
        }

        return $this->parent->createFromStream($source);
    }
}
