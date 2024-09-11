<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests\Functional;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Parser\Tests\Functional\Stub\AstNode;
use Phplrt\Parser\Tests\TestCase as BaseTestCase;
use Phplrt\Visitor\Traverser;
use Phplrt\Visitor\Visitor;

abstract class TestCase extends BaseTestCase
{
    protected function analyze(iterable $ast): array
    {
        $result = [];

        Traverser::through(new class ($result) extends Visitor {
            private array $result;

            public function __construct(array &$result)
            {
                $this->result = &$result;
            }

            /**
             * @param NodeInterface|AstNode $node
             */
            public function enter(NodeInterface $node): void
            {
                $this->result[] = [$node->name, \iterator_count($node->getIterator())];
            }
        })
            ->traverse($ast);

        return $result;
    }
}
