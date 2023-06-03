<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Stmt;

/**
 * @internal This is an internal class, please do not use it in your application code.
 * @psalm-internal Phplrt\Compiler
 * @psalm-suppress PropertyNotSetInConstructor
 */
class RuleStmt extends Statement
{
    /**
     * @var non-empty-string
     */
    public string $name;

    /**
     * @param non-empty-string $name
     */
    public function __construct(string $name)
    {
        assert($name !== '', 'Rule name must not be empty');

        $this->name = $name;
    }
}
