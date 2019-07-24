<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Dependency\Reader;

use Phplrt\Assembler\Context\ContextInterface;
use Phplrt\Assembler\Context\ContextVisitor;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Class ClassFilter
 */
class ClassFilter extends NodeVisitorAbstract
{
    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var \ReflectionClass
     */
    private $reflection;

    /**
     * @param \ReflectionClass $reflection
     */
    public function __construct(\ReflectionClass $reflection)
    {
        $this->context    = new ContextVisitor();
        $this->reflection = $reflection;
    }

    /**
     * @param Node $node
     * @return int|Node|null
     */
    public function enterNode(Node $node)
    {
        return $this->context->enterNode($node);
    }

    /**
     * @param Node $node
     * @return int|null
     */
    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof ClassLike) {
            return $this->apply($node);
        }

        return null;
    }

    /**
     * @param ClassLike $class
     * @return int|null
     */
    private function apply(ClassLike $class): ?int
    {
        if ($this->context->fqn($class->name)->toString() !== $this->reflection->getName()) {
            return NodeTraverser::REMOVE_NODE;
        }

        return null;
    }
}
