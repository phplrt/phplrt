<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Visitor;

interface ExecutorInterface
{
    /**
     * @param iterable<object> $nodes
     * @return iterable<object>
     */
    public function execute(iterable $nodes): iterable;
}
