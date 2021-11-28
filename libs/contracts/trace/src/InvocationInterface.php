<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Trace;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

interface InvocationInterface extends PositionInterface
{
    /**
     * @return ReadableInterface
     */
    public function getSource(): ReadableInterface;
}
