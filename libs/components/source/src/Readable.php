<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Hash\HasherInterface;

/**
 * An arbitrary object that supports reading of source data
 */
abstract class Readable implements ReadableInterface
{
    public function __construct(
        protected HasherInterface $hasher,
    ) {}
}
