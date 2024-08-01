<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Stmt;

/**
 * @internal this is an internal class, please do not use it in your application code
 * @psalm-internal Phplrt\Compiler
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AlternationStmt extends Statement
{
    /**
     * @param array<Statement> $statements
     */
    public function __construct(
        public array $statements,
    ) {}

    /**
     * @return \Traversable<non-empty-string, array<Statement>>
     */
    public function getIterator(): \Traversable
    {
        yield 'statements' => $this->statements;
    }
}
