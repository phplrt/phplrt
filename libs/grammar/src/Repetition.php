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
     * @param int $from
     * @param int|float $to
     */
    public function __construct(
        public readonly int|string $rule,
        public readonly int $from = 0,
        public readonly int|float $to = \INF
    ) {
        assert($to >= $from, new \InvalidArgumentException(
            'Min repetitions count must be greater or equal than max repetitions'
        ));

        assert(!\is_float($to) || $to === \INF, new \InvalidArgumentException(
            '"Less than" criterion must be an integer or float(INF) value, but ' . $to . ' passed'
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function reduce(BufferInterface $buffer, \Closure $reduce): ?iterable
    {
        [$children, $iterations] = [[], 0];

        $position = $buffer->key();

        do {
            /** @psalm-suppress MixedAssignment */
            if (($result = $reduce($this->rule)) === null) {
                if ($iterations >= $this->from && $iterations <= $this->to) {
                    $buffer->seek($buffer->key());

                    return $children;
                }

                $buffer->seek($position);

                return null;
            }

            $children = $this->mergeWith($children, $result);
        } while (++$iterations);

        return $children;
    }
}
