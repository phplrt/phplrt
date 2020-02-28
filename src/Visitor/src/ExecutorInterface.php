<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Visitor;

/**
 * Interface ExecutorInterface
 */
interface ExecutorInterface
{
    /**
     * @param iterable $ast
     * @return iterable
     */
    public function execute(iterable $ast): iterable;
}
