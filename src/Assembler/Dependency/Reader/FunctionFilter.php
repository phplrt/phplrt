<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Dependency\Reader;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\Function_;
use Phplrt\Assembler\Context\ContextVisitor;
use Phplrt\Assembler\Context\ContextInterface;

/**
 * Class FunctionFilter
 */
class FunctionFilter extends NodeVisitorAbstract
{
    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var \ReflectionFunction
     */
    private $reflection;

    /**
     * @param \ReflectionFunction $reflection
     */
    public function __construct(\ReflectionFunction $reflection)
    {
        $this->context = new ContextVisitor();
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
        if ($node instanceof Function_) {
            return $this->apply($node);
        }

        return null;
    }

    /**
     * @param Function_ $function
     * @return int|null
     */
    private function apply(Function_ $function): ?int
    {
        if ($this->context->fqn($function->name)->toString() !== $this->reflection->getName()) {
            return NodeTraverser::REMOVE_NODE;
        }

        return null;
    }
}
