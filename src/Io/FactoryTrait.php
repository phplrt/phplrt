<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Io;

use Phplrt\Io\File\Physical;
use Phplrt\Io\File\Virtual;

/**
 * Trait FactoryTrait
 */
trait FactoryTrait
{
    /**
     * {@inheritDoc}
     */
    public static function fromSplFileInfo(\SplFileInfo $info): Readable
    {
        return static::fromPathname($info->getPathname());
    }

    /**
     * {@inheritDoc}
     */
    public static function fromPathname(string $path): Readable
    {
        return new Physical($path);
    }

    /**
     * {@inheritDoc}
     */
    public static function fromSources(string $sources = '', string $name = null): Readable
    {
        return new Virtual($sources, $name);
    }

    /**
     * {@inheritDoc}
     */
    public static function empty(string $name = null): Readable
    {
        return new Virtual('', $name);
    }
}
