<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Stmt;

/**
 * @internal this is an internal class, please do not use it in your application code
 * @psalm-internal Phplrt\Compiler
 */
class TokenStmt extends Statement
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $name,
        public bool $keep,
    ) {
        assert($name !== '', 'Token name must not be empty');
    }
}
