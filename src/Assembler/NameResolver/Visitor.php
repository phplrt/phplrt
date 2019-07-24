<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\NameResolver;

use Phplrt\Assembler\Context\ContextInterface;
use Phplrt\Assembler\Context\ContextVisitor;
use Phplrt\Assembler\NameResolver\Resolver\Booleans;
use Phplrt\Assembler\NameResolver\Resolver\Classes;
use Phplrt\Assembler\NameResolver\Resolver\Constants;
use Phplrt\Assembler\NameResolver\Resolver\Functions;
use Phplrt\Assembler\NameResolver\Resolver\Nulls;
use Phplrt\Assembler\NameResolver\Resolver\ResolverInterface;
use Phplrt\Assembler\NameResolver\Resolver\SpecialNames;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeVisitorAbstract;

/**
 * Class DependenciesResolver
 */
class Visitor extends NodeVisitorAbstract
{
    /**
     * @var \Closure
     */
    private $lookup;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var array|ResolverInterface[]
     */
    private $resolvers;

    /**
     * DependenciesResolver constructor.
     *
     * @param \Closure $lookup
     */
    public function __construct(\Closure $lookup)
    {
        $this->lookup  = $lookup;
        $this->context = new ContextVisitor();

        $this->resolvers = [
            new Nulls(),
            new Booleans(),
            new SpecialNames(),
            new Constants($this->context),
            new Classes($this->context),
            new Functions($this->context),
        ];
    }

    /**
     * @param Node $node
     * @return mixed|void
     */
    public function enterNode(Node $node)
    {
        if ($result = $this->context->enterNode($node)) {
            return $result;
        }
    }

    /**
     * @param Node $node
     * @return mixed|void
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassLike || $node instanceof Function_) {
            $node->name = ($this->lookup)($this->context->fqn($node->name))->getLast();
        }

        if ($node instanceof Name) {
            if ($node === $this->context->namespace()) {
                return;
            }

            foreach ($this->resolvers as $resolver) {
                if ($out = $resolver->resolve($node, $this->lookup)) {
                    return $out;
                }
            }

            return $node;
        }

        return null;
    }
}
