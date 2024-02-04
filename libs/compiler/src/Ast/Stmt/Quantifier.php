<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Stmt;

use Phplrt\Compiler\Ast\Node;

/**
 * @internal This is an internal class, please do not use it in your application code.
 * @psalm-internal Phplrt\Compiler
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Quantifier extends Node
{
    /**
     * @var int<0, max>
     */
    public int $from;

    /**
     * @var int<0, max>|float
     */
    public $to;

    /**
     * @param int<0, max> $from
     * @param float|int<0, max> $to
     */
    public function __construct(int $from, float $to)
    {
        assert($from >= 0, 'Minimal repetition times must be greater or equal than 0');
        assert($to >= 0, 'Maximum repetition times must be greater or equal than 0');

        $this->from = $from;
        $this->to = \is_infinite($to) ? $to : (int) $to;
    }
}
