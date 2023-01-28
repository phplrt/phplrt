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
class RepetitionStmt extends Statement
{
    /**
     * @var Statement
     */
    public Statement $statement;

    /**
     * @var Quantifier
     */
    public Quantifier $quantifier;

    /**
     * @param Statement $stmt
     * @param Quantifier $quantifier
     */
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
