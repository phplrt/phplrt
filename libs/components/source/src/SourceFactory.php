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
    /**
     * @param HasherInterface $hasher The hasher instance used for
     *        generating content hashes
     */
    public function __construct(
        public HasherInterface $hasher = new XXHash3Hasher(),
    ) {}

    /**
     * @api
     *
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

    /**
     * @api
     *
     * @param non-empty-string|null $pathname
     *
     * @phpstan-return ($pathname is null ? Source : VirtualFile)
     */
    public function createEmpty(?string $pathname = null): Source|VirtualFile
    {
        return $this->createFromString('', $pathname);
    }

    /**
     * @api
     *
     * @throws SourceExceptionInterface
     */
    public function createFromSplFileInfo(\SplFileInfo $inf): File
    {
        return $this->createFromFile($inf->getPathname());
    }

    /**
     * @api
     */
    public function createFromFile(string $pathname): File
    {
        if ($pathname === '') {
            throw NotCreatableException::becauseSourceIs('empty pathname');
        }

        return new File($pathname, $this->hasher);
    }

    /**
     * @api
     *
     * @phpstan-return ($pathname is null ? Source : VirtualFile)
     */
    public function createFromString(string $content, ?string $pathname = null): Source|VirtualFile
    {
        if ($pathname === null) {
            return new Source($content, $this->hasher);
        }

        return new VirtualFile($pathname, $content, $this->hasher);
    }

    /**
     * @api
     *
     * @phpstan-return ($pathname is null ? Stream : VirtualFileStream)
     */
    public function createFromStream(mixed $stream, ?string $pathname = null): Stream|VirtualFileStream
    {
        if (!\is_resource($stream)) {
            throw NotCreatableException::becauseSourceIs('non-resource');
        }

        if (\get_resource_type($stream) !== 'stream') {
            throw NotCreatableException::becauseSourceIs('non-stream resource');
        }

        if ($pathname === null) {
            return new Stream($stream, $this->hasher);
        }

        return new VirtualFileStream($pathname, $stream, $this->hasher);
    }
}
