<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Visitor;

use Phplrt\Contracts\Ast\NodeInterface;

interface ExecutorInterface
{
    /**
     * @param iterable<NodeInterface> $ast
     * @return iterable<NodeInterface>
     */
    public function execute(iterable $ast): iterable;
}
