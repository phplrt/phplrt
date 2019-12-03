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
 * Class DelegateStmt
 * @internal Compiler's grammar AST node class
 */
class ClassDelegateStmt extends DelegateStmt
{
    /**
     * ClassDelegateStmt constructor.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        $code = \sprintf('return new \\%s((string)$state, (array)$children, $offset);', \ltrim($class, '\\'));

        parent::__construct($code);
    }
}
