<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Ast;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * @internal Node is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Compiler
 */
abstract class Node implements NodeInterface
{
    /**
     * @var ReadableInterface
     */
    public ReadableInterface $file;

    /**
     * @var int
     */
    public int $offset = 0;

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return new \EmptyIterator();
    }
}
