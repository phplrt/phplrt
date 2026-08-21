<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Source\Stream\StringStream;

/**
 * Implementing a readable object that references a source code as a string value
 *
 * @final please do not inherit from this class
 */
class StringSource extends Readable
{
    /**
     * @var int<0, max>
     */
    public int $size {
        get => \strlen($this->content);
    }

    public function __construct(
        public readonly string $content = '',
    ) {}

    public function createStream(): StringStream
    {
        // The content is already held in memory, so the cursor reads it as it
        // is rather than through a resource of its own.
        return new StringStream($this->content);
    }
}
