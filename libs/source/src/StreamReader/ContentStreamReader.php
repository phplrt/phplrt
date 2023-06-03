<?php

declare(strict_types=1);

namespace Phplrt\Source\StreamReader;

use Phplrt\Source\Exception\NotAccessibleException;

class ContentStreamReader implements StreamReaderInterface
{
    /**
     * @var string
     */
    private const MEMORY_FILENAME = 'php://memory';

    /**
     * @var string
     */
    private const MEMORY_MODE = 'rb+';

    /**
     * @var string
     */
    private const ERROR_MEMORY_OPENING = 'Can not open ' . self::MEMORY_FILENAME . ' for writing';

    /**
     * @var string
     */
    private const ERROR_MEMORY_WRITING = 'Can not write content data into ' . self::MEMORY_FILENAME;

    /**
     * @var string
     */
    private const ERROR_MEMORY_NON_REWINDABLE = self::MEMORY_FILENAME . ' is not rewindable';

    /**
     * @var string
     */
    private string $content;

    /**
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        /** @var resource $memory */
        $memory = \fopen(self::MEMORY_FILENAME, self::MEMORY_MODE);

        if (@\fwrite($memory, $this->content) === false) {
            throw new NotAccessibleException(self::ERROR_MEMORY_WRITING);
        }

        if (!@\rewind($memory)) {
            throw new NotAccessibleException(self::ERROR_MEMORY_NON_REWINDABLE);
        }

        return $memory;
    }
}
