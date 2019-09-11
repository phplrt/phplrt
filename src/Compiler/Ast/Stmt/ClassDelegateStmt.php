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
     * @param int $offset
     */
    public function __construct(string $class, int $offset)
    {
        $code = \sprintf('return new \\%s($name, $children, $offset);', \ltrim($class, '\\'));

        parent::__construct($code, $offset);
    }
}
