<?php

declare(strict_types=1);

namespace Phplrt\Exception\Analysis\Internal;

/**
 * Tells where an error of a particular kind has occurred.
 *
 * @internal this is an internal library interface, please do not use it in your code
 * @psalm-internal Phplrt\Exception
 */
interface FailureLocatorInterface
{
    /**
     * Returns the place the given error tells about itself, or {@see null} in
     * case it tells nothing this locator understands.
     */
    public function tryLocate(\Throwable $e): ?FailureLocation;
}
