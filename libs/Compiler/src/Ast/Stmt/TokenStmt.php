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
 * Class TokenStmt
 * @internal Compiler's grammar AST node class
 */
class TokenStmt extends Statement
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $keep;

    /**
     * TokenUsage constructor.
     *
     * @param string $name
     * @param bool $keep
     */
    public function __construct(string $name, bool $keep)
    {
        $this->name = $name;
        $this->keep = $keep;
    }
}
