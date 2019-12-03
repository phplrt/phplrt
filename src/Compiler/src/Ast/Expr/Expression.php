<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Expr;

use Phplrt\Compiler\Ast\Node;

/**
 * Class Expression
 * @internal Compiler's grammar AST node class
 */
abstract class Expression extends Node
{
    /**
     * @return string
     */
    abstract public function render(): string;
}
