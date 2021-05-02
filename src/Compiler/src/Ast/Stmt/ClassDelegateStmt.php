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
 * @internal ClassDelegateStmt is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Compiler
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
        $code = \sprintf('return new \\%s($state, $children, $offset);', \ltrim($class, '\\'));

        parent::__construct($code);
    }
}
