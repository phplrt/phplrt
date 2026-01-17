<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Source\Hash\HasherInterface;

/**
 * Implementing a readable object that references a virtual (non-real)
 * file with a predefined content stream
 */
class VirtualFileStream extends Stream implements FileInterface
{
    public function __construct(
        /**
         * The virtual file pathname
         *
         * @var non-empty-string
         */
        public readonly string $pathname,
        mixed $stream,
        HasherInterface $hasher,
    ) {
        parent::__construct($stream, $hasher);
    }
}
