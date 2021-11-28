<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;

interface FactoryInterface
{
    /**
     * @param mixed $source
     * @return ReadableInterface
     */
    public function create(mixed $source): ReadableInterface;

    /**
     * @param string $source
     * @param non-empty-string|null $pathname
     * @return ReadableInterface
     * @psalm-return ($pathname is string ? FileInterface : ReadableInterface)
     */
    public function fromSource(string $source, string $pathname = null): ReadableInterface;

    /**
     * @param resource $stream
     * @param non-empty-string|null $pathname
     * @return ReadableInterface
     * @psalm-return ($pathname is string ? FileInterface : ReadableInterface)
     */
    public function fromResourceStream(mixed $stream, string $pathname = null): ReadableInterface;

    /**
     * @param \SplFileInfo $info
     * @return FileInterface
     */
    public function fromSplFileInfo(\SplFileInfo $info): FileInterface;

    /**
     * @param non-empty-string $pathname
     * @return FileInterface
     */
    public function fromPathname(string $pathname): FileInterface;
}
