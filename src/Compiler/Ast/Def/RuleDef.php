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
    public $keep = true;

    /**
     * Rule constructor.
     *
     * @param string $name
     * @param DelegateStmt $delegate
     * @param Statement $body
     * @param int $offset
     */
    public function __construct(string $name, DelegateStmt $delegate, Statement $body, int $offset)
    {
        $this->name     = $name;
        $this->body     = $body;
        $this->delegate = $delegate;

        parent::__construct($offset);
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
