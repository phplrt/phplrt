<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

interface SourceFactoryInterface
{
    /**
     * @throws SourceExceptionInterface in case of an error in creating the source object
     */
    public function createFromFile(string $pathname): FileInterface;

    /**
     * @param non-empty-string|null $pathname
     * @phpstan-return ($pathname is null ? ReadableInterface : FileInterface)
     * @throws SourceExceptionInterface in case of an error in creating the source object
     */
    public function createFromStream(mixed $stream, ?string $pathname = null): ReadableInterface;

    /**
     * @param non-empty-string|null $pathname
     * @phpstan-return ($pathname is null ? ReadableInterface : FileInterface)
     * @throws SourceExceptionInterface in case of an error in creating the source object
     */
    public function createFromString(string $content, ?string $pathname = null): ReadableInterface;
}
