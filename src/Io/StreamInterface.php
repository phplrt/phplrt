<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Io;

use Psr\Http\Message\StreamInterface as PsrStreamInterface;

/**
 * Interface StreamInterface
 */
interface StreamInterface extends PsrStreamInterface
{
    /**
     * Gets string line from file pointer.
     *
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function readLine(): string;

    /**
     * Acquire a shared lock for reading.
     *
     * @throws \RuntimeException if an error occurs.
     * @return void
     */
    public function lock(): void;

    /**
     * Unlock resource.
     *
     * @throws \RuntimeException if an error occurs.
     * @return void
     */
    public function unlock(): void;
}
