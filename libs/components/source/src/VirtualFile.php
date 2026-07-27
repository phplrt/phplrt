<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FileInterface;

/**
 * Implementing a readable object that references a virtual (non-real)
 * file with predefined content string
 */
class VirtualFile extends Source implements FileInterface
{
    public function __construct(
        /**
         * The virtual file pathname
         *
         * @var non-empty-string
         */
        public readonly string $pathname,
        string $content = '',
    ) {
        parent::__construct($content);
    }
}
