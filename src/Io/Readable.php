<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Io;

use Phplrt\Position\PositionProviderInterface;

/**
 * Interface Readable
 */
interface Readable extends PositionProviderInterface, ContentProviderInterface
{
    /**
     * Returns the path to the file.
     *
     * @return string
     */
    public function getPathname(): string;

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
