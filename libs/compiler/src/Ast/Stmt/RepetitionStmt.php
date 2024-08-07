<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Stmt;

/**
 * @internal this is an internal class, please do not use it in your application code
 * @psalm-internal Phplrt\Compiler
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class RepetitionStmt extends Statement
{
    public Statement $statement;

    public Quantifier $quantifier;

    public function __construct(Statement $stmt, Quantifier $quantifier)
    {
        $this->statement = $stmt;
        $this->quantifier = $quantifier;
    }

    /**
     * @return \Traversable<non-empty-string, Statement|Quantifier>
     */
    public function getIterator(): \Traversable
    {
        yield 'statement' => $this->statement;
        yield 'quantifier' => $this->quantifier;
    }
}
