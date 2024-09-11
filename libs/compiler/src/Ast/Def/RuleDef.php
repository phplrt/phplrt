<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Def;

use Phplrt\Compiler\Ast\Stmt\DelegateStmt;
use Phplrt\Compiler\Ast\Stmt\Statement;

/**
 * @internal this is an internal class, please do not use it in your application code
 * @psalm-internal Phplrt\Compiler
 */
class RuleDef extends Definition
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $name,
        public DelegateStmt $delegate,
        public Statement $body,
        public bool $keep = true,
    ) {
        assert($name !== '', 'Rule name must not be empty');
    }

    public function getIterator(): \Traversable
    {
        yield 'body' => $this->body;
    }
}
