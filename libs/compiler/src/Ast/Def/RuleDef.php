<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Def;

use Phplrt\Compiler\Ast\Stmt\Statement;
use Phplrt\Compiler\Ast\Stmt\DelegateStmt;

/**
 * @internal This is an internal class, please do not use it in your application code.
 * @psalm-internal Phplrt\Compiler
 * @psalm-suppress PropertyNotSetInConstructor
 */
class RuleDef extends Definition
{
    /**
     * @var non-empty-string
     */
    public string $name;

    /**
     * @var DelegateStmt
     */
    public DelegateStmt $delegate;

    /**
     * @var Statement
     */
    public Statement $body;

    /**
     * @var bool
     */
    public bool $keep;

    /**
     * @param non-empty-string $name
     * @param DelegateStmt $delegate
     * @param Statement $body
     * @param bool $keep
     */
    public function __construct(string $name, DelegateStmt $delegate, Statement $body, bool $keep = true)
    {
        assert($name !== '', 'Rule name must not be empty');

        $this->name = $name;
        $this->body = $body;
        $this->delegate = $delegate;
        $this->keep = $keep;
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator([
            'body' => $this->body,
        ]);
    }
}
