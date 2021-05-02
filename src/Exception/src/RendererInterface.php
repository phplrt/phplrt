<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Position\IntervalInterface;
use Phplrt\Contracts\Source\ReadableInterface;

interface RendererInterface
{
    /**
     * @param \Throwable $e
     * @return string
     */
    public function render(\Throwable $e): string;

    /**
     * @param \Throwable $e
     * @param ReadableInterface $source
     * @param IntervalInterface $position
     * @return string
     */
    public function renderIn(\Throwable $e, ReadableInterface $source, IntervalInterface $position): string;
}
