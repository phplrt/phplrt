<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Dependency\Reader;

use Phplrt\Assembler\Exception\AssemblerException;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Class EntryAnalyzer
 */
class EntryAnalyzer extends NodeVisitorAbstract
{
    /**
     * @var string[]
     */
    private const SUPPORTED_NODES = [
        Node\Stmt\Declare_::class,
        Node\Stmt\Namespace_::class,
        Node\Stmt\Use_::class,
        Node\Stmt\GroupUse::class,
        Node\Stmt\ClassLike::class,

        // Allow optional compilation
        Node\Stmt\If_::class,
    ];

    /**
     * @param array|Node[] $nodes
     * @return Node[]|void|null
     */
    public function beforeTraverse(array $nodes)
    {
        foreach ($nodes as $node) {
            if (! \in_array(\get_class($node), self::SUPPORTED_NODES, true)) {
                $error = '%s can not be assembled, because provides a side effects';

                throw new AssemblerException(\sprintf($error, $node->getType()));
            }
        }
    }
}
