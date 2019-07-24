<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Context;

use Phplrt\Assembler\Exception\DependencyException;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_ as UseExpression;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Class ContextVisitor
 */
class ContextVisitor extends NodeVisitorAbstract implements ContextInterface
{
    /**
     * @var Name
     */
    private $namespace;

    /**
     * @var Aliases
     */
    private $aliases;

    /**
     * @param Node $node
     * @return int|Node|void|null
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Namespace_) {
            $this->namespace = $node->name;
            $this->aliases   = null;
        }

        if ($node instanceof GroupUse) {
            $this->visitGroupUse($node);

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof UseExpression) {
            $this->visitUseExpression($node);

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
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
        $aliases = $this->uses();

        $aliases->register($name, $alias ? $alias->name : null, $this->typeOfImport($type));
    }

    /**
     * @return Aliases
     */
    public function uses(): Aliases
    {
        return $this->aliases ?? $this->aliases = new Aliases();
    }

    /**
     * @param int $type
     * @return int
     */
    private function typeOfImport(int $type): int
    {
        switch ($type) {
            case UseExpression::TYPE_NORMAL:
                return Aliases::TYPE_CLASS;

            case UseExpression::TYPE_FUNCTION:
                return Aliases::TYPE_FUNCTION;

            case UseExpression::TYPE_CONSTANT:
                return Aliases::TYPE_CONST;

            default:
                throw new DependencyException('Can not resolve unsupported use expression');
        }
    }

    /**
     * @param UseExpression $expression
     * @return void
     */
    private function visitUseExpression(UseExpression $expression): void
    {
        foreach ($expression->uses as $stmt) {
            $this->register($stmt->name, $stmt->alias, $expression->type);
        }
    }

    /**
     * @param Identifier|Name $name
     * @return Name
     */
    public function fqn($name): Name
    {
        $parts = $name instanceof Name ? $name->parts : [$name->name];
        $parts = $this->namespace ? \array_merge($this->namespace->parts, $parts) : $parts;

        return new Name(\implode('\\', $parts));
    }

    /**
     * @return Name|null
     */
    public function namespace(): ?Name
    {
        return $this->namespace;
    }
}
