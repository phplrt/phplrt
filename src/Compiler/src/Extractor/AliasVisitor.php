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
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\ClassLike;

/**
 * Class AliasVisitor
 */
class AliasVisitor extends NodeVisitorAbstract
{
    /**
     * @var array|Name[]
     */
    private $replaces = [];

    /**
     * AliasVisitor constructor.
     *
     * @param array $replaces
     */
    public function __construct(array $replaces)
    {
        foreach ($replaces as $fqn => $alias) {
            $this->replaces[$alias] = new Name($fqn);
        }
    }

    /**
     * @param Node $node
     * @return Name|null
     */
    public function enterNode(Node $node): ?Name
    {
        switch (true) {
            case $node instanceof Name:
                foreach ($this->replaces as $alias => $name) {
                    if ($name->parts === $node->parts) {
                        return new Name($alias);
                    }
                }

                break;

            case $node instanceof ClassLike:
                /** @var Name $needle */
                $needle = $node->getAttribute('fqn');

                if (! $needle) {
                    return null;
                }

                foreach ($this->replaces as $alias => $name) {
                    if ($name->parts === $needle->parts) {
                        $node->name = new Name($alias);

                        return null;
                    }
                }

                break;
        }

        return null;
    }
}
