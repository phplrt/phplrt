<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Stmt;

use Phplrt\Contracts\Ast\NodeInterface;

/**
 * @internal RepetitionStmt is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\compiler
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
        $this->statement  = $stmt;
        $this->quantifier = $quantifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator([
            'statement'  => $this->statement,
            'quantifier' => $this->quantifier,
        ]);
    }
}
