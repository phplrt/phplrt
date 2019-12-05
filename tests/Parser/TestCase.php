<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Tests\Parser;

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

            public function enter(NodeInterface $node)
            {
                $this->result[] = [$node->getOffset(), \iterator_count($node->getIterator())];
            }
        })
            ->traverse($ast);

        return $result;
    }
}
