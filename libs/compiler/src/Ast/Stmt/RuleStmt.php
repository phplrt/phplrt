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
 * Class RuleStmt
 * @internal Compiler's grammar AST node class
 */
class RuleStmt extends Statement
{
    /**
     * @var string
     */
    public $name;

    /**
     * RuleInvocation constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
