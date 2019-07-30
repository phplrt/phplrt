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

/**
 * Class Traverser
 */
class Traverser implements TraverserInterface
{
    /**
     * @var string
     */
    private const ERROR_ENTER_RETURN_TYPE = '%s::enter() returned invalid value of type %s';

    /**
     * @var string
     */
    private const ERROR_LEAVE_RETURN_TYPE = '%s::leave() returned invalid value of type %s';

    /**
     * @var string
     */
    private const ERROR_LEAVE_NOT_IMPLEMENTED = '%s::leave() returns an unsupported value of type %s';

    /**
     * @var string
     */
    private const ERROR_ROOT_REMOVING = 'Visitor::leave() caused the removal of the root node that cannot be deleted';

    /**
     * @var string
     */
    private const ERROR_READONLY_MODIFY = 'Can not modify the readonly "%s" attribute of %s node';

    /**
     * @var \SplObjectStorage|VisitorInterface[]
     */
    private $visitors;

    /**
     * Traverser constructor.
     */
    public function __construct()
    {
        $this->visitors = new \SplObjectStorage();
    }

    /**
     * @param VisitorInterface $visitor
     * @return TraverserInterface|$this
     */
    public function with(VisitorInterface $visitor): TraverserInterface
    {
        $this->visitors->attach($visitor);

        return $this;
    }

    /**
     * @param VisitorInterface $visitor
     * @return TraverserInterface|$this
     */
    public function without(VisitorInterface $visitor): TraverserInterface
    {
        $this->visitors->detach($visitor);

        return $this;
    }

    /**
     * @param NodeInterface $node
     * @return NodeInterface
     */
    public function traverse(NodeInterface $node): NodeInterface
    {
        $node = $this->before($node) ?? $node;

        if (($node = $this->each($node)) === null) {
            throw new \LogicException(self::ERROR_ROOT_REMOVING);
        }

        return $this->after($node) ?? $node;
    }

    /**
     * @param NodeInterface $node
     * @return array
     */
    private function enter(NodeInterface $node): array
    {
        foreach ($this->visitors as $visitor) {
            $return = $visitor->enter($node);

            switch (true) {
                case $return === null:
                    break;

                case $return instanceof NodeInterface:
                    return [$return, true];

                case $return === TraverserInterface::DONT_TRAVERSE_CHILDREN:
                    return [$node, false];

                case $return === TraverserInterface::STOP_TRAVERSAL:
                    break 2;

                default:
                    $message = \sprintf(self::ERROR_ENTER_RETURN_TYPE, $this->className($visitor), \gettype($return));
                    throw new \LogicException($message);
            }
        }

        return [$node, true];
    }

    /**
     * @param NodeInterface $node
     * @return NodeInterface|null
     */
    private function each(NodeInterface $node): ?NodeInterface
    {
        [$new, $withChildren] = $this->enter($node);

        if ($withChildren) {
            $this->visit($new);
        }

        return $this->leave($new);
    }

    /**
     * @param NodeInterface $node
     * @return void
     */
    private function visit(NodeInterface $node): void
    {
        foreach ($node as $index => $current) {
            $value = $this->each($current);

            try {
                if ($current !== $value) {
                    $node->$index = $value;
                }
            } catch (\Error $e) {
                $message = \sprintf(self::ERROR_READONLY_MODIFY, $index, $this->className($node));
                throw new \LogicException($message);
            }
        }
    }

    /**
     * @param object $object
     * @return string
     */
    private function className($object): string
    {
        $class = \explode("\0", \get_class($object))[0];

        $suffix = \function_exists('\\spl_object_id') ? \spl_object_id($object) : \spl_object_hash($object);

        return $class . '#' . $suffix;
    }

    /**
     * @param NodeInterface $node
     * @return NodeInterface|null
     */
    private function leave(NodeInterface $node): ?NodeInterface
    {
        foreach ($this->visitors as $visitor) {
            $return = $visitor->leave($node);

            switch (true) {
                case $return === null:
                    break;

                case $return instanceof NodeInterface:
                    return $return;

                case \is_array($return):
                    $message = \sprintf(self::ERROR_LEAVE_NOT_IMPLEMENTED, $this->className($visitor), \gettype($return));
                    throw new \LogicException($message);

                case $return === TraverserInterface::REMOVE_NODE:
                    return null;

                default:
                    $message = \sprintf(self::ERROR_LEAVE_RETURN_TYPE, $this->className($visitor), \gettype($return));
                    throw new \LogicException($message);
            }
        }

        return $node;
    }

    /**
     * @param NodeInterface $node
     * @return NodeInterface
     */
    private function before(NodeInterface $node): NodeInterface
    {
        foreach ($this->visitors as $visitor) {
            $node = $visitor->before($node) ?? $node;
        }

        return $node;
    }

    /**
     * @param NodeInterface $node
     * @return NodeInterface
     */
    private function after(NodeInterface $node): NodeInterface
    {
        foreach ($this->visitors as $visitor) {
            $node = $visitor->after($node) ?? $node;
        }

        return $node;
    }
}
