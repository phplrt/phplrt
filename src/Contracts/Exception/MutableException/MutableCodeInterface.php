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
 * Interface MutableCodeInterface
 */
interface MutableCodeInterface
{
    /**
     * @param int $code
     * @return MutableCodeInterface|$this
     */
    public function withCode(int $code = 0): self;
}
