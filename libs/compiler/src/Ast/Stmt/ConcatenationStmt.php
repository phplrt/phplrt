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
 * @internal ConcatenationStmt is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\compiler
 */
class ConcatenationStmt extends Statement
{
    /**
     * @var Statement[]
     */
    public array $statements = [];

    /**
     * Sequence constructor.
     *
     * @param array|Statement[] $statements
     */
    public function __construct(array $statements)
    {
        $this->statements = $statements;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator([
            'statements' => $this->statements
        ]);
    }
}
