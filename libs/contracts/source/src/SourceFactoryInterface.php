<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

interface SourceFactoryInterface
{
    /**
     * @return ($source is \SplFileInfo
     *      ? FileInterface
     *      : ($source is FileInterface
     *          ? FileInterface
     *          : ReadableInterface)
     *  )
     *
     * @throws SourceExceptionInterface In case of an error in creating the
     *         source object.
     */
    public function create(mixed $source): ReadableInterface;

    /**
     * Create a new source object from a string.
     *
     * @param string $content String content with which to populate the
     *        source object.
     * @param non-empty-string|null $name The name of the source if you want to
     *        create a {@see FileInterface} object.
     *
     * @return ($name is null ? ReadableInterface : FileInterface)
     *
     * @throws SourceExceptionInterface In case of an error in creating the
     *         source object.
     */
    public function createFromString(string $content = '', string $name = null): ReadableInterface;

    /**
     * Create a source object from an existing file.
     *
     * The file MAY be opened using the "rb" mode, in the case that the
     * implementation implies reading through {@see fopen()}. In addition, this
     * implementation MUST also include a lock on such a file using
     * {@see flock()} function with {@see LOCK_SH} operation.
     *
     * The {@see $filename} MAY be any string supported by {@see fopen()}
     * or {@see file_get_contents()} functions.
     *
     * @psalm-taint-sink file $filename
     * @param non-empty-string $filename
     *
     * @throws SourceExceptionInterface In case of an error in creating the
     *         source object.
     */
    public function createFromFile(string $filename): FileInterface;

    /**
     * Create a new source object from an existing (non-closed) resource stream.
     *
     * The stream MUST be readable and MAY NOT be writable.
     *
     * @param resource $stream The PHP resource stream to use as basis of
     *        source object.
     * @param non-empty-string|null $name The name of the source if you want to
     *        create a {@see FileInterface} object.
     *
     * @return ($name is null ? ReadableInterface : FileInterface)
     *
     * @throws SourceExceptionInterface In case of an error in creating the
     *         source object.
     */
    public function createFromStream(mixed $stream, string $name = null): ReadableInterface;
}
