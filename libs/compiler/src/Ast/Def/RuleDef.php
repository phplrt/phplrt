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
 * Class RuleDef
 * @internal Compiler's grammar AST node class
 */
class RuleDef extends Definition
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var DelegateStmt|null
     */
    public $delegate;

    /**
     * @var Statement
     */
    public $body;

    /**
     * @var bool
     */
    public $keep;

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
        $this->name     = $name;
        $this->body     = $body;
        $this->delegate = $delegate;
        $this->keep     = $keep;
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
