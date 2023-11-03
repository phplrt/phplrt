<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

interface ReadableInterface
{
    /**
     * Returns the resource stream of the source.
     *
     * @return resource Returns the streaming contents of a file.
     *
     * @throws SourceExceptionInterface May occur during the inability to
     *         open or some operations with the resource stream.
     */
    public function getStream();

    /**
     * Returns the contents of the source.
     *
     * @return string Returns the string contents of a file.
     *
     * @throws SourceExceptionInterface May occur when it is not possible to
     *         read source's data and/or convert it to a string.
     */
    public function getContents(): string;

    /**
     * Returns the identifier (hash) of the source object.
     *
     * If the value of the source content changes, the hash value will
     * also be changed.
     *
     * @return non-empty-string Returns hash of a file.
     *
     * @throws SourceExceptionInterface May occur when it is not possible to
     *         convert object state information into a hash.
     */
    public function getHash(): string;
}
