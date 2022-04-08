<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Stmt;

use Phplrt\Compiler\Ast\Node;

/**
 * Class Quantifier
 * @internal Compiler's grammar AST node class
 */
class Quantifier extends Node
{
    /**
     * @var int
     */
    public $from;

    /**
     * @var int|float
     */
    public $to;

    /**
     * Quantifier constructor.
     *
     * @param int $from
     * @param float $to
     */
    public function __construct(int $from, float $to)
    {
        $this->from = $from;
        $this->to   = \is_infinite($to) ? $to : (int)$to;
    }
}
