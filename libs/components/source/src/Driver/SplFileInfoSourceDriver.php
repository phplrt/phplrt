<?php

declare(strict_types=1);

namespace Phplrt\Source\Driver;

use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\File;

/**
 * Creates a source out of a reference to a physical file.
 */
final readonly class SplFileInfoSourceDriver implements SourceDriverInterface
{
    /**
     * @throws NotCreatableException in case the reference carries no pathname
     */
    public function tryCreate(mixed $source): ?File
    {
        if (!$source instanceof \SplFileInfo) {
            return null;
        }

        $pathname = $source->getPathname();

        if ($pathname === '') {
            throw NotCreatableException::becauseSourceIs('empty pathname');
        }

        return new File($pathname);
    }
}
