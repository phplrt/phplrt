<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Ast;

use Phplrt\Source\FileInterface;
use Phplrt\Source\ReadableInterface;
use Phplrt\Contracts\Ast\NodeInterface;

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
