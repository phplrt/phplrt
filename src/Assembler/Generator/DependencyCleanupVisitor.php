<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Generator;

use PhpParser\Node;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Class DependencyCleanupVisitor
 */
class DependencyCleanupVisitor extends NodeVisitorAbstract
{
    /**
     * @param Node $node
     * @return mixed|void
     */
    public function leaveNode(Node $node)
    {
        if ($out = $this->removeNamespace($node)) {
            return $out;
        }

        if ($out = $this->removeUses($node)) {
            return $out;
        }
    }

    /**
     * @param Node $node
     * @return array|null
     */
    private function removeNamespace(Node $node): ?array
    {
        if ($node instanceof Namespace_) {
            return $node->stmts;
        }

        return null;
    }

    /**
     * @param Node $node
     * @return int|null
     */
    private function removeUses(Node $node): ?int
    {
        if ($node instanceof GroupUse) {
            return NodeTraverser::REMOVE_NODE;
        }

        if ($node instanceof Use_) {
            return NodeTraverser::REMOVE_NODE;
        }

        return null;
    }
}
