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
 * @internal TokenStmt is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\compiler
 */
class TokenStmt extends Statement
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var bool
     */
    public bool $keep;

    /**
     * @param string $name
     * @param bool $keep
     */
    public function __construct(string $name, bool $keep)
    {
        $this->name = $name;
        $this->keep = $keep;
    }
}
