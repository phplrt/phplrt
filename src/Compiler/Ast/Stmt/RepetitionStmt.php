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
 * Class RepetitionStmt
 * @internal Compiler's grammar AST node class
 */
class RepetitionStmt extends Statement
{
    /**
     * @var Statement
     */
    public $statement;

    /**
     * @var Quantifier
     */
    public $quantifier;

    /**
     * Choice constructor.
     *
     * @param Statement $stmt
     * @param Quantifier $quantifier
     * @param int $offset
     */
    public function __construct(Statement $stmt, Quantifier $quantifier, int $offset)
    {
        $this->statement  = $stmt;
        $this->quantifier = $quantifier;

        parent::__construct($offset);
    }

    /**
     * @return \Traversable|NodeInterface[]
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator([
            'statement'  => $this->statement,
            'quantifier' => $this->quantifier,
        ]);
    }
}
