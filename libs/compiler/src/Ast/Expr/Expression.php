<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Expr;

use Phplrt\Compiler\Ast\Node;

/**
 * @internal this is an internal class, please do not use it in your application code
 * @psalm-internal Phplrt\Compiler
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class Expression extends Node
{
    /**
     * @return non-empty-string
     */
    abstract public function render(): string;
}
