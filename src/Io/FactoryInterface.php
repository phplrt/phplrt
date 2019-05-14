<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Io;

/**
 * Interface FactoryInterface
 */
interface FactoryInterface
{
    /**
     * Creates a new Readable instance using the supplied source code
     * and an optional file name.
     *
     * @param string $sources
     * @param string|null $name
     * @return Readable
     */
    public static function fromSources(string $sources = '', string $name = null): Readable;

    /**
     * Creates a new Readable instance using pathname of existing file.
     *
     * @param string $path
     * @return Readable
     */
    public static function fromPathname(string $path): Readable;

    /**
     * Creates a new Readable instance using PHP \SplFileInfo class.
     *
     * @param \SplFileInfo $info
     * @return Readable
     */
    public static function fromSplFileInfo(\SplFileInfo $info): Readable;

    /**
     * Creates a new empty Readable instance with optional filename.
     *
     * @param string|null $name
     * @return Readable
     */
    public static function empty(string $name = null): Readable;
}
