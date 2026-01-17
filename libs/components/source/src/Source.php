<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Source\Exception\NotAccessibleException;
use Phplrt\Source\Hash\HasherInterface;

/**
 * Implementing a readable object that references a source code as a string value
 */
class Source extends Readable
{
    public mixed $stream {
        /**
         * @throws NotAccessibleException When the stream cannot be created or accessed
         */
        get {
            $stream = @\fopen('php://memory', 'rb+');

            if ($stream === false) {
                throw NotAccessibleException::becauseInternalErrorOccurs(\error_get_last());
            }

            if (@\fwrite($stream, $this->content) === false) {
                throw NotAccessibleException::becauseStreamIsNotWritable('php://memory');
            }

            if (@\rewind($stream) === false) {
                throw NotAccessibleException::becauseStreamIsNotSeekable('php://memory');
            }

            return $stream;
        }
    }

    public string $hash {
        get => $this->hash ??= $this->hasher->content($this->content);
    }

    public function __construct(
        public readonly string $content,
        HasherInterface $hasher,
    ) {
        parent::__construct($hasher);
    }
}
