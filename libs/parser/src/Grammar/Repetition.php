<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;

class Repetition extends Production
{
    /**
     * @var int<0, max>
     * @readonly
     */
    public int $gte;

    /**
     * @var int<0, max>|\INF
     * @readonly
     */
    public $lte;

    /**
     * @var array-key
     * @readonly
     */
    public $rule;

    /**
     * @param array-key $rule
     * @param int<0, max> $gte
     * @param int<0, max>|\INF $lte
     */
    public function __construct($rule, int $gte = 0, $lte = \INF)
    {
        \assert($lte >= $gte, 'Min repetitions count must be greater or equal than max repetitions');

        $this->rule = $rule;
        $this->gte  = $gte;
        $this->lte  = \is_infinite($lte) ? \INF : (int)$lte;
    }

    /**
     * @param int<0, max> $times
     */
    public function from(int $times): self
    {
        $this->gte = \max(0, $times);

        return $this;
    }

    /**
     * @param int<0, max> $times
     */
    public function to(int $times): self
    {
        $this->lte = $times;

        return $this;
    }

    public function inf(): self
    {
        $this->lte = \INF;

        return $this;
    }

    public function reduce(BufferInterface $buffer, \Closure $reduce): ?iterable
    {
        $children = [];
        $iterations = 0;

        $global = $buffer->key();

        do {
            $inRange  = $iterations >= $this->gte && $iterations <= $this->lte;
            $rollback = $buffer->key();

            if (($result = $reduce($this->rule)) === null) {
                if (! $inRange) {
                    $buffer->seek($global);

                    return null;
                }

                $buffer->seek($rollback);

                return $children;
            }

            $children = $this->mergeWith($children, $result);
            ++$iterations;
        } while ($result !== null);

        return $children;
    }
}
