<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Exception\MutableException;

/**
 * Trait MutableCodeTrait
 *
 * @mixin MutableCodeInterface
 * @mixin \Exception
 */
trait MutableCodeTrait
{
    /**
     * @param int $code
     * @return MutableCodeInterface|$this
     */
    public function withCode(int $code = 0): MutableCodeInterface
    {
        $this->code = $code;

        return $this;
    }
}
