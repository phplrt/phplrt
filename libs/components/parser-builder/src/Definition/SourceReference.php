<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition;

use Phplrt\Contracts\Source\ReadableInterface;

/**
 * The place of the source code a definition has been written in.
 */
final readonly class SourceReference
{
    public function __construct(
        /**
         * The source the definition has been written in.
         */
        public ReadableInterface $source,
        /**
         * The byte offset the definition starts at.
         *
         * @var int<0, max>
         */
        public int $offset,
        /**
         * The size of the definition in bytes.
         *
         * @var int<0, max>
         */
        public int $length = 0,
    ) {}
}
