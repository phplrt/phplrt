<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Stmt;

/**
 * @internal This is an internal class, please do not use it in your application code.
 * @psalm-internal Phplrt\Compiler
 * @psalm-suppress PropertyNotSetInConstructor
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
