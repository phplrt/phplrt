<?php

declare(strict_types=1);

namespace Phplrt\Source\Factory;

use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\File;
use Phplrt\Source\Hash\HasherInterface;
use Phplrt\Source\Hash\XXHash3Hasher;

/**
 * @template-implements SourceFactoryInterface<\SplFileInfo>
 */
final readonly class FileSourceFactory implements SourceFactoryInterface
{
    public function __construct(
        private HasherInterface $hasher = new XXHash3Hasher(),
    ) {}

    public function supports(mixed $source): bool
    {
        return $source instanceof \SplFileInfo;
    }

    public function create(mixed $source, ?string $virtualPathname = null): ReadableInterface
    {
        /** @phpstan-ignore-next-line : Additional type check */
        if (!$source instanceof \SplFileInfo) {
            throw NotCreatableException::becauseSourceIs($source, \SplFileInfo::class);
        }

        $pathname = $source->getPathname();

        if ($pathname === '') {
            throw NotCreatableException::becauseSourceIsTypeOf(
                actual: \sprintf('%s(pathname: "")', $source::class),
                expected: \sprintf('%s(pathname: non-empty-string)', $source::class),
            );
        }

        return new File($pathname, $this->hasher);
    }
}
