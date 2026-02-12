<?php

declare(strict_types=1);

namespace Phplrt\Source\Factory;

use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Hash\HasherInterface;
use Phplrt\Source\Hash\XXHash3Hasher;
use Phplrt\Source\Source;
use Phplrt\Source\VirtualFile;

/**
 * @template-implements SourceFactoryInterface<string>
 */
final readonly class StringSourceFactory implements SourceFactoryInterface
{
    public function __construct(
        private HasherInterface $hasher = new XXHash3Hasher(),
    ) {}

    public function supports(mixed $source): bool
    {
        return \is_string($source);
    }

    public function create(mixed $source, ?string $virtualPathname = null): ReadableInterface
    {
        /** @phpstan-ignore-next-line : Additional type check */
        if (!\is_string($source)) {
            throw NotCreatableException::becauseSourceIs($source, 'string');
        }

        if ($virtualPathname === null) {
            return new Source($source, $this->hasher);
        }

        return new VirtualFile($virtualPathname, $source, $this->hasher);
    }
}
