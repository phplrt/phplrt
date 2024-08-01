<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Def;

use Phplrt\Compiler\Ast\Stmt\DelegateStmt;
use Phplrt\Compiler\Ast\Stmt\Statement;

/**
 * @internal this is an internal class, please do not use it in your application code
 * @psalm-internal Phplrt\Compiler
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class RuleDef extends Definition
{
    /**
     * @var non-empty-string
     */
    public string $name;

    public DelegateStmt $delegate;

    public Statement $body;

    public bool $keep;

    /**
     * @param non-empty-string $name
     */
    public function __construct(string $name, DelegateStmt $delegate, Statement $body, bool $keep = true)
    {
        assert($name !== '', 'Rule name must not be empty');

        $this->name = $name;
        $this->body = $body;
        $this->delegate = $delegate;
        $this->keep = $keep;
    }

    public function getIterator(): \Traversable
    {
        yield 'body' => $this->body;
    }
}
