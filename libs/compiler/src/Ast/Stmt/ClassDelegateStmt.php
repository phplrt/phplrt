<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Stmt;

/**
 * @internal this is an internal class, please do not use it in your application code
 * @psalm-internal Phplrt\Compiler
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ClassDelegateStmt extends DelegateStmt
{
    /**
     * @param class-string $class
     */
    public function __construct(string $class)
    {
        assert($class !== '', 'Class name must not be empty');

        $code = \sprintf('return new \\%s($state, $children, $offset);', \ltrim($class, '\\'));

        parent::__construct($code);
    }
}
