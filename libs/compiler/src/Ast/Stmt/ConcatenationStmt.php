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
 * @internal Compiler's grammar AST node class
 */
class ConcatenationStmt extends Statement
{
    /**
     * @var Statement[]
     */
    public array $statements = [];

    /**
     * @param array|Statement[] $statements
     */
    public function __construct(array $statements)
    {
        $this->statements = $statements;
    }

    /**
     * @return \Traversable|NodeInterface[][]|Statement[][]
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator([
            'statements' => $this->statements
        ]);
    }
}
