<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Grammar;

use Phplrt\Contracts\Lexer\BufferInterface;

class Repetition extends Production
{
    /**
     * @var int
     */
    public int $gte;

    /**
     * @var int|float
     */
    public $lte;

    /**
     * @var int|string
     */
    public $rule;

    /**
     * Repetition constructor.
     *
     * @param int|string $rule
     * @param int $gte
     * @param int|float $lte
     */
    public function __construct($rule, int $gte = 0, $lte = \INF)
    {
        \assert($lte >= $gte, 'Min repetitions count must be greater or equal than max repetitions');

        $this->rule = $rule;
        $this->gte  = $gte;
        $this->lte  = \is_infinite($lte) ? \INF : (int)$lte;
    }

    /**
     * @param int $times
     * @return $this
     */
    public function from(int $times): self
    {
        $this->gte = \max(0, $times);

        return $this;
    }

    /**
     * @param int $times
     * @return $this
     */
    public function to(int $times): self
    {
        $this->lte = $times;

        return $this;
    }

    /**
     * @return $this
     */
    public function inf(): self
    {
        $this->lte = \INF;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function reduce(BufferInterface $buffer, \Closure $reduce): ?iterable
    {
        [$children, $iterations] = [[], 0];

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
        } while ($result !== null && ++$iterations);

        return $children;
    }
}
