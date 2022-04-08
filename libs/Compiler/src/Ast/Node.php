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
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Class Node
 *
 * @internal Compiler's grammar AST node class
 */
abstract class Node implements NodeInterface
{
    /**
     * @var ReadableInterface|FileInterface
     */
    public $file;

    /**
     * @var int
     */
    public $offset = 0;

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \EmptyIterator();
    }
}
