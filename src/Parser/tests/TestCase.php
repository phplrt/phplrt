<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Tests;

use Phplrt\Parser\Tests\Stub\AstNode;
use Phplrt\Visitor\Visitor;
use Phplrt\Visitor\Traverser;
use Phplrt\Contracts\Ast\NodeInterface;
use PHPUnit\Framework\TestCase as BastTestCase;

/**
 * Class TestCase
 */
abstract class TestCase extends BastTestCase
{
    /**
     * @param iterable $ast
     * @return array
     */
    protected function analyze(iterable $ast): array
    {
        $result = [];

        Traverser::through(new class ($result) extends Visitor {
            private $result;

            public function __construct(array &$result)
            {
                $this->result =& $result;
            }

            /**
             * @param NodeInterface|AstNode $node
             * @return mixed|void|null
             */
            public function enter(NodeInterface $node)
            {
                $this->result[] = [$node->name, \iterator_count($node->getIterator())];
            }
        })
            ->traverse($ast);

        return $result;
    }
}
