<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Grammar;

use Phplrt\Contracts\Buffer\BufferInterface;

class Repetition extends Production
{
    /**
     * Repetition constructor.
     *
     * @param int|string $rule
     * @param int $gte
     * @param int|float $lte
     */
    public function __construct(
        public readonly int|string $rule,
        public readonly int $gte = 0,
        public readonly int|float $lte = \INF
    ) {
        assert($lte >= $gte, new \InvalidArgumentException(
            'Min repetitions count must be greater or equal than max repetitions'
        ));

        assert(!\is_float($lte) || $lte === \INF, new \InvalidArgumentException(
            '"Less than" criterion must be an integer or float(INF) value, but ' . $lte . ' passed'
        ));
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
            ++$iterations;
        } while ($result !== null);

        return $children;
    }
}
