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
 * @internal Compiler's grammar AST node class
 */
class DelegateStmt extends Statement
{
    /**
     * @var string|null
     */
    public ?string $code;

    /**
     * @param string|null $code
     */
    public function __construct(?string $code)
    {
        $this->code = $code;
    }
}
