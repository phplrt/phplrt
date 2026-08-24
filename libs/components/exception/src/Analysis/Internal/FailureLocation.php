<?php

declare(strict_types=1);

namespace Phplrt\Exception\Analysis\Internal;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\Analysis\FailureInterval;

/**
 * The place an error has occurred at: the source and the fragment of it.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Exception
 */
final readonly class FailureLocation
{
    public function __construct(
        /**
         * The source the error occurred in.
         */
        public ReadableInterface $source,
        /**
         * The fragment of the source the error occurred in, or {@see null} in
         * case the error tells nothing about the size of it.
         */
        public ?FailureInterval $interval = null,
    ) {}
}
