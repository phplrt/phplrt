<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Trace;

use Phplrt\Contracts\Trace\TraceInterface;

interface FactoryInterface
{
    /**
     * @param positive-int|0 $depth
     * @return TraceInterface
     */
    public function create(int $depth = 0): TraceInterface;
}
