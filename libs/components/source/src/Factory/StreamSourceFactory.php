<?php

declare(strict_types=1);

namespace Phplrt\Source\Factory;

use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Hash\HasherInterface;
use Phplrt\Source\Hash\XXHash3Hasher;
use Phplrt\Source\Stream;
use Phplrt\Source\VirtualFileStream;

/**
 * @template-implements SourceFactoryInterface<resource>
 */
final readonly class StreamSourceFactory implements SourceFactoryInterface
{
    public function __construct(
        private HasherInterface $hasher = new XXHash3Hasher(),
    ) {}

    public function supports(mixed $source): bool
    {
        return \is_resource($source)
            && \get_resource_type($source) === 'stream';
    }

    public function create(mixed $source, ?string $virtualPathname = null): ReadableInterface
    {
        if (!\is_resource($source)) {
            throw NotCreatableException::becauseSourceIs($source, 'resource');
        }

        if (\get_resource_type($source) !== 'stream') {
            $type = \get_resource_type($source);

            if ($type === '') {
                $type = '*unknown*';
            }

            throw NotCreatableException::becauseSourceIsTypeOf($type, 'resource(stream)');
        }

        $virtualPathname ??= $this->findNameFromStream($source);

        if ($virtualPathname === null) {
            return new Stream($source, $this->hasher);
        }

        return new VirtualFileStream($virtualPathname, $source, $this->hasher);
    }

    /**
     * @param resource $stream
     *
     * @return non-empty-string|null
     */
    private function findNameFromStream(mixed $stream): ?string
    {
        $metadata = \stream_get_meta_data($stream);

        if (($uri = $metadata['uri'] ?? null) === '') {
            return null;
        }

        return $uri;
    }
}
