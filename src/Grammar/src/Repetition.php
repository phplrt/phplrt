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

/**
 * Class Repetition
 */
class Repetition extends Production
{
    /**
     * @var int
     */
    public $gte;

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
     * @param int|float $gte
     * @param int|float $lte
     */
    public function __construct($rule, int $gte = 0, float $lte = \INF)
    {
        \assert($lte >= $gte, 'Min repetitions count must be greater or equal than max repetitions');

        $this->rule = $rule;
        $this->gte  = $gte;
        $this->lte  = \is_infinite($lte) ? INF : (int)$lte;
    }

    /**
     * @return array
     */
    public function getConstructorArguments(): array
    {
        return [$this->rule, $this->gte, $this->lte];
    }

    /**
     * @param BufferInterface $buffer
     * @param \Closure $reduce
     * @return iterable|null
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
