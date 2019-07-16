<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Contracts\Exception\MutableException;

/**
 * Interface MutableFileInterface
 */
interface MutableFileInterface
{
    /**
     * @param string $pathname
     * @return MutableFileInterface|$this
     */
    public function withFile(string $pathname): self;
}
