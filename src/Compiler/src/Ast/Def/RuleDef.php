<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Def;

use Phplrt\Compiler\Ast\Stmt\DelegateStmt;
use Phplrt\Compiler\Ast\Stmt\Statement;

/**
 * @internal RuleDef is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Compiler
 */
class RuleDef extends Definition
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var DelegateStmt|null
     */
    public ?DelegateStmt $delegate = null;

    /**
     * @var Statement
     */
    public Statement $body;

    /**
     * @var bool
     */
    public bool $keep;

    /**
     * Rule constructor.
     *
     * @param string $name
     * @param DelegateStmt $delegate
     * @param Statement $body
     * @param bool $keep
     */
    public function __construct(string $name, DelegateStmt $delegate, Statement $body, bool $keep = true)
    {
        $this->name = $name;
        $this->body = $body;
        $this->delegate = $delegate;
        $this->keep = $keep;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator([
            'body' => $this->body,
        ]);
    }
}
