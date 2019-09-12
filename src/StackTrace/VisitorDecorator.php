<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\StackTrace;

use Phplrt\Visitor\VisitorInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\StackTrace\Record\NodeRecord;

/**
 * Class VisitorDecorator
 */
class VisitorDecorator implements VisitorInterface
{
    /**
     * @var Trace
     */
    private $trace;

    /**
     * @var VisitorInterface
     */
    private $visitor;

    /**
     * Visitor constructor.
     *
     * @param Trace $trace
     * @param VisitorInterface $visitor
     */
    public function __construct(Trace $trace, VisitorInterface $visitor)
    {
        $this->trace = $trace;
        $this->visitor = $visitor;
    }

    /**
     * {@inheritDoc}
     */
    public function before(iterable $nodes): ?iterable
    {
        return $this->visitor->before($nodes);
    }

    /**
     * {@inheritDoc}
     */
    public function enter(NodeInterface $node)
    {
        $result = $this->visitor->enter($node);

        if ($node instanceof TraceableNodeInterface) {
            $this->trace->push(new NodeRecord($node->getFile(), $node));
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     * @throws \Throwable
     */
    public function leave(NodeInterface $node)
    {
        $result = $this->visitor->leave($node);

        if ($node instanceof TraceableNodeInterface) {
            $this->trace->pop();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function after(iterable $nodes): ?iterable
    {
        return $this->visitor->after($nodes);
    }
}
