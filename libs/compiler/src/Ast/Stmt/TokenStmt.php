<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Stmt;

/**
 * @internal this is an internal class, please do not use it in your application code
 * @psalm-internal Phplrt\Compiler
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TokenStmt extends Statement
{
    /**
     * @var non-empty-string
     */
    public string $name;

    public bool $keep;

    /**
     * @param non-empty-string $name
     */
    public function __construct(string $name, bool $keep)
    {
        assert($name !== '', 'Token name must not be empty');

        $this->name = $name;
        $this->keep = $keep;
    }
}
