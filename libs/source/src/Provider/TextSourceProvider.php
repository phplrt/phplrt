<?php

declare(strict_types=1);

namespace Phplrt\Source\Provider;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\SourceFactory;

final class TextSourceProvider implements SourceProviderInterface
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
        if (!\is_string($source)) {
            return null;
        }

        return $this->parent->createFromString($source);
    }
}
