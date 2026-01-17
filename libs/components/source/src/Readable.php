<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Hash\HasherInterface;
use Phplrt\Source\Internal\SourceFactoryProvider;

/**
 * An arbitrary object that supports reading of source data
 */
abstract class Readable implements ReadableInterface
{
    use SourceFactoryProvider;

    /**
     * @param HasherInterface $hasher The hasher instance used for
     *        generating content hashes
     */
    public function __construct(
        protected HasherInterface $hasher,
    ) {}
}
