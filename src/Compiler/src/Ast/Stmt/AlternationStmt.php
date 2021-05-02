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
 * @internal AlternationStmt is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Compiler
 */
class AlternationStmt extends Statement
{
    /**
     * @var Statement[]
     */
    public array $statements = [];

    /**
     * Choice constructor.
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
