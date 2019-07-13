<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Contracts\Io;

use Phplrt\Contracts\Position\PositionProviderInterface;

/**
 * Interface Readable
 */
interface Readable extends PositionProviderInterface
{
    /**
     * Returns the path to the file.
     *
     * @return string
     */
    public function getPathname(): string;

    /**
     * Returns the full contents of the source.
     *
     * @return string
     */
    public function getContents(): string;

    /**
     * @return resource
     */
    public function getStream();

    /**
     * Returns the hash of the file.
     *
     * @return string
     */
    public function getHash(): string;

    /**
     * Returns information whether the file actually exists on the file system.
     *
     * @return bool
     */
    public function exists(): bool;
}
