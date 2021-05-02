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
 * @internal Quantifier is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Compiler
 */
class Quantifier extends Node
{
    /**
     * @var int
     */
    public int $from;

    /**
     * @var int|float
     */
    public $to;

    /**
     * @param int $from
     * @param float $to
     */
    public function __construct(int $from, float $to)
    {
        $this->from = $from;
        $this->to   = \is_infinite($to) ? $to : (int)$to;
    }
}
