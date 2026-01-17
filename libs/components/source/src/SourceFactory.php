<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Hash\HasherInterface;
use Phplrt\Source\Hash\XXHash3Hasher;

final class SourceFactory implements SourceFactoryInterface
{
    public function __construct(
        public HasherInterface $hasher = new XXHash3Hasher(),
    ) {}

    /**
     * @throws NotCreatableException in case the source argument is not valid
     * @throws SourceExceptionInterface in case of source creation exception occurs
     */
    public function create(mixed $source): ReadableInterface
    {
        return match (true) {
            $source instanceof \SplFileInfo => $this->createFromFile($source->getPathname()),
            \is_string($source) => $this->createFromString($source),
            \is_resource($source) => $this->createFromStream($source),
            default => throw NotCreatableException::becauseSourceIsInvalid($source),
        };
    }

    public function createFromFile(string $pathname): File
    {
        if ($pathname === '') {
            throw NotCreatableException::becauseSourceIs('empty pathname');
        }

        return new File($pathname, $this->hasher);
    }

    /**
     * @phpstan-return ($name is null ? Source : VirtualFile)
     */
    public function createFromString(string $content = '', ?string $name = null): Source|VirtualFile
    {
        if ($name === null) {
            return new Source($content, $this->hasher);
        }

        return new VirtualFile($name, $content, $this->hasher);
    }

    /**
     * @phpstan-return ($name is null ? Stream : VirtualFileStream)
     */
    public function createFromStream(mixed $stream, ?string $name = null): Stream|VirtualFileStream
    {
        if (!\is_resource($stream)) {
            throw NotCreatableException::becauseSourceIs('non-resource');
        }

        if (\get_resource_type($stream) !== 'stream') {
            throw NotCreatableException::becauseSourceIs('non-stream resource');
        }

        if ($name === null) {
            return new Stream($stream, $this->hasher);
        }

        return new VirtualFileStream($name, $stream, $this->hasher);
    }
}
