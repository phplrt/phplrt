<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Ast;

use Phplrt\StackTrace\TraceableNodeInterface;

/**
 * Class Node
 * @internal Compiler's grammar AST node class
 */
abstract class Node implements TraceableNodeInterface
{
    /**
     * @var int
     */
    private $offset;

    /**
     * @var string
     */
    public $file;

    /**
     * Node constructor.
     *
     * @param int $offset
     */
    public function __construct(int $offset)
    {
        $this->offset = $offset;
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file ?? '{internal}';
    }

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
