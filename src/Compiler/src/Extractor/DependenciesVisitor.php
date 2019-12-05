<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Extractor;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\NodeTraverser;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name\FullyQualified;

/**
 * Class DependenciesVisitor
 */
class DependenciesVisitor extends NodeVisitorAbstract
{
    /**
     * @var array
     */
    private $contexts = [];

    /**
     * @var array|string[]
     */
    private $currentNamespace = [];

    /**
     * @var bool
     */
    private $skipNextName = false;

    /**
     * @var array|ClassLike[]
     */
    private $classes = [];

    /**
     * @param Node $node
     * @return int|void|Node
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof ClassLike && $node->name) {
            $this->classes[$this->ctx() . '\\' . $node->name->name] = $node;

            $fqn = \array_merge($this->currentNamespace, [$node->name->name]);
            $node->setAttribute('fqn', new Name($fqn));
        }

        switch (true) {
            case $node instanceof Namespace_:
                $this->currentNamespace = $node->name->parts;
                $this->skipNextName = true;

                return;

            case $node instanceof GroupUse:
                $this->visitGroupUse($node);

                return NodeTraverser::DONT_TRAVERSE_CHILDREN;

            case $node instanceof Use_:
                $this->visitUseExpression($node);

                return NodeTraverser::DONT_TRAVERSE_CHILDREN;

            case $node instanceof ConstFetch:
                $this->skipNextName = true;

                return;

            case $node instanceof Name:
                if ($this->skipNextName || $node->isSpecialClassName()) {
                    $this->skipNextName = false;

                    return;
                }

                return $this->normalize($node);
        }
    }

    /**
     * @param GroupUse $group
     * @return void
     */
    private function visitGroupUse(GroupUse $group): void
    {
        foreach ($group->uses as $use) {
            $namespace = \array_merge($group->prefix->parts, $use->name->parts);

            $this->register(new Name(\implode('\\', $namespace)), $use->alias, $group->type);
        }
    }

    /**
     * @param Name $name
     * @param Identifier|null $alias
     * @param int $type
     * @return void
     */
    private function register(Name $name, ?Identifier $alias, int $type): void
    {
        $key = $alias ? $alias->name : \end($name->parts);

        $this->contexts[$this->ctx()][$key] = [$name, $type];
    }

    /**
     * @return string
     */
    private function ctx(): string
    {
        return \implode('\\', $this->currentNamespace);
    }

    /**
     * @param Use_ $expression
     * @return void
     */
    private function visitUseExpression(Use_ $expression): void
    {
        foreach ($expression->uses as $stmt) {
            $this->register($stmt->name, $stmt->alias, $expression->type);
        }
    }

    /**
     * @param Name $name
     * @return Name
     */
    private function normalize(Name $name): Name
    {
        $parts = $name->parts;
        $key = \array_shift($parts);

        if ($use = $this->contexts[$this->ctx()][$key] ?? null) {
            $fqn = \array_merge($use[0]->parts, $parts);

            return new FullyQualified($fqn, $name->getAttributes());
        }

        $fqn = new FullyQualified(\array_merge($this->currentNamespace, $name->parts));

        if (
            \class_exists($fqn->toString()) ||
            \interface_exists($fqn->toString()) ||
            \trait_exists($fqn->toString())
        ) {
            return $fqn;
        }

        return new FullyQualified($name);
    }

    /**
     * @param Node $node
     * @return int|Node|Node[]|void|null
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Namespace_) {
            $this->currentNamespace = [];

            return $node->stmts;
        }

        if ($node->getDocComment()) {
            $node->setAttribute('comments', []);
        }
    }

    /**
     * @param array $nodes
     * @return array|Node[]|ClassLike[]|null
     */
    public function afterTraverse(array $nodes): array
    {
        return \array_values($this->classes);
    }

    /**
     * @return array|ClassLike[]
     */
    public function getClasses(): array
    {
        return $this->classes;
    }
}
