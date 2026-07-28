<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Source\Exception\NotCreatableException;

final class SourceFactory implements SourceFactoryInterface
{
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

        return new File($pathname);
    }

    /**
     * @api
     *
     * @phpstan-return ($pathname is null ? Source : VirtualFile)
     */
    public function createFromString(string $content, ?string $pathname = null): Source|VirtualFile
    {
        if ($pathname === null) {
            return new Source($content);
        }

        return new VirtualFile($pathname, $content);
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

        if ($pathname === null) {
            return new Stream($stream);
        }

        return new VirtualFileStream($pathname, $stream);
    }
}
