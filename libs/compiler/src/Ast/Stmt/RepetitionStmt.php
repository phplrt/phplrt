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
    public function __construct(
        public readonly Statement $statement,
        public readonly Quantifier $quantifier,
    ) {}

    /**
     * @return \Traversable<non-empty-string, Statement|Quantifier>
     */
    public function getIterator(): \Traversable
    {
        yield 'statement' => $this->statement;
        yield 'quantifier' => $this->quantifier;
    }
}
