<?php

declare(strict_types=1);

namespace Phplrt\Source\Provider;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\SourceFactory;
use Psr\Http\Message\StreamInterface;

final class PsrStreamSourceProvider implements SourceProviderInterface
{
    /**
     * @readonly
     */
    private SourceFactory $parent;

    public function __construct(SourceFactory $parent)
    {
        $this->parent = $parent;
    }

    public function create(mixed $source): ?ReadableInterface
    {
        if (!$source instanceof StreamInterface) {
            return null;
        }

        return $this->parent->createFromStream($source->detach());
    }
}
