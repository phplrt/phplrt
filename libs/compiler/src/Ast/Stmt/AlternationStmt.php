<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Stmt;

/**
 * @internal This is an internal class, please do not use it in your application code.
 * @psalm-internal Phplrt\Compiler
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AlternationStmt extends Statement
{
    /**
     * @var array<Statement>
     */
    public array $statements = [];

    /**
     * @param array<Statement> $statements
     */
    public function __construct(array $statements)
    {
        $this->statements = $statements;
    }

    /**
     * @return \Traversable<non-empty-string, array<Statement>>
     */
    public function getIterator(): \Traversable
    {
        yield 'statements' => $this->statements;
    }
}
