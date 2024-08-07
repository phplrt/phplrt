<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Stmt;

/**
 * @internal this is an internal class, please do not use it in your application code
 * @psalm-internal Phplrt\Compiler
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DelegateStmt extends Statement
{
    public ?string $code;

    public function __construct(?string $code)
    {
        $this->code = $code;
    }
}
