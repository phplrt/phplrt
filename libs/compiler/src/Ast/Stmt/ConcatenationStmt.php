<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Stmt;

/**
 * @internal this is an internal class, please do not use it in your application code
 * @psalm-internal Phplrt\Compiler
 */
class ConcatenationStmt extends Statement
{
    public function __construct(
        /**
         * @var array<array-key, Statement>
         */
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
