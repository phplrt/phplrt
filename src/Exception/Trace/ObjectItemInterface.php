<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Exception\Trace;

/**
 * Interface ObjectItemInterface
 */
interface ObjectItemInterface extends FunctionItemInterface
{
    /**
     * @return bool
     */
    public function isStaticCall(): bool;

    /**
     * @return string
     */
    public function getClass(): string;
}
