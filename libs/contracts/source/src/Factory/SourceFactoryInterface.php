<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source\Factory;

use Phplrt\Contracts\Source\Exception\SourceCreationExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * @template T of mixed = mixed
 */
interface SourceFactoryInterface
{
    /**
     * Returns {@see true} in case of passed `$source` is
     * supported by the factory.
     *
     * @phpstan-assert-if-true T $source
     */
    public function supports(mixed $source): bool;

    /**
     * Creates the {@see ReadableInterface} instance if possible.
     *
     * The second nullable `$virtualPathname` argument is used to create virtual
     * file objects that may not exist in the actual filesystem in cases where,
     * among other things, the input data does not contain enough information to
     * indicate that this data was obtained from any source (for example, a
     * {@see string} or {@see resource}).
     *
     * @param T $source
     * @param non-empty-string|null $virtualPathname
     *
     * @phpstan-return ($virtualPathname is null ? ReadableInterface : FileInterface)
     *
     * @throws SourceCreationExceptionInterface in case of source creation exception occurs
     */
    public function create(mixed $source, ?string $virtualPathname = null): ReadableInterface;
}
