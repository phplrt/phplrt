<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Builder;

use Phplrt\Compiler\Ast\Expr\Expression;
use Phplrt\Contracts\Ast\NodeInterface;

/**
 * Class StackTrace
 */
class StackTrace extends Visitor
{
    /**
     * @var \SplObjectStorage
     */
    private $stack;

    /**
     * StackTrace constructor.
     *
     * @param \SplFileInfo $file
     * @param \SplObjectStorage $stack
     */
    public function __construct(\SplFileInfo $file, \SplObjectStorage $stack)
    {
        $this->stack = $stack;

        parent::__construct($file);
    }

    /**
     * @param NodeInterface $node
     * @return void
     */
    public function enter(NodeInterface $node): void
    {
        if ($node instanceof Expression) {
            $this->stack->attach($node, $this->file);
        }
    }

    /**
     * @param NodeInterface $node
     * @return void
     */
    public function leave(NodeInterface $node): void
    {
        if ($node instanceof Expression) {
            $this->stack->detach($node);
        }
    }
}
