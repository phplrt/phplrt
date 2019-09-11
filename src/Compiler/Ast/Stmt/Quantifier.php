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
     * @var float
     */
    public $from;

    /**
     * @var float
     */
    public $to;

    /**
     * Quantifier constructor.
     *
     * @param float $from
     * @param float $to
     * @param int $offset
     */
    public function __construct(float $from, float $to, int $offset)
    {
        $this->from = $from;
        $this->to = $to;

        parent::__construct($offset);
    }
}
