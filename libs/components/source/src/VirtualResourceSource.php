<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FileInterface;

/**
 * Implementing a readable object that references a virtual (non-real)
 * file with a predefined content stream
 *
 * @final please do not inherit from this class
 */
class VirtualResourceSource extends ResourceSource implements FileInterface
{
    /**
     * @param resource $stream The resource stream
     */
    public function __construct(
        /**
         * The virtual file pathname
         *
         * @var non-empty-string
         */
        public readonly string $pathname,
        mixed $stream,
        bool $autoclose = false,
    ) {
        parent::__construct($stream, $autoclose);
    }
}
