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
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Class CodePurifierVisitor
 */
class CodePurifierVisitor extends NodeVisitorAbstract
{
    /**
     * @param Node $node
     * @return mixed|void
     */
    public function leaveNode(Node $node)
    {
        $this->removeDocBlock($node);

        if ($out = $this->removeDeclare($node)) {
            return $out;
        }
    }

    /**
     * @param Node $node
     * @return void
     */
    private function removeDocBlock(Node $node): void
    {
        if ($node->hasAttribute('comments')) {
            $node->setAttribute('comments', null);
        }
    }

    /**
     * @param Node $node
     * @return int|null
     */
    private function removeDeclare(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\Declare_) {
            return NodeTraverser::REMOVE_NODE;
        }

        return null;
    }
}
