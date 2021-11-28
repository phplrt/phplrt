<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Trace\Printer;

use Phplrt\Contracts\Trace\TraceInterface;

interface PrinterInterface
{
    /**
     * @param TraceInterface $trace
     * @return non-empty-string
     */
    public function print(TraceInterface $trace): string;
}
