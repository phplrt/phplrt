<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Source\Hash\HasherInterface;

/**
 * Implementing a readable object that references a virtual (non-real)
 * file with predefined content stream
 */
class VirtualFileStream extends Stream implements FileInterface
{
    /**
     * @param resource $stream
     */
    public function __construct(
        /**
         * @var non-empty-string
         */
        public readonly string $pathname,
        mixed $stream,
        HasherInterface $hasher,
    ) {
        parent::__construct($stream, $hasher);
    }
}
