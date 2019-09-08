<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Rule;

use Phplrt\Parser\Buffer\BufferInterface;

/**
 * Class Repetition
 */
class Repetition extends Production
{
    /**
     * @var int|float
     */
    private $gte;

    /**
     * @var int|float
     */
    private $lte;

    /**
     * @var int|string
     */
    private $rule;

    /**
     * Repetition constructor.
     *
     * @param int|string $rule
     * @param int|float $gte
     * @param int|float $lte
     */
    public function __construct($rule, float $gte = 0, float $lte = \INF)
    {
        \assert($lte >= $gte, 'Min repetitions count must be greater or equal than max repetitions');

        $this->rule = $rule;
        $this->gte  = $gte;
        $this->lte  = $lte;
    }

    /**
     * @param BufferInterface $buffer
     * @param \Closure $reduce
     * @return iterable|null
     */
    public function reduce(BufferInterface $buffer, \Closure $reduce): ?iterable
    {
        [$children, $iterations] = [[], 0];

        do {
            [$valid, $rollback] = [
                $iterations >= $this->gte && $iterations <= $this->lte,
                $buffer->key(),
            ];

            $result = $reduce($this->rule);

            if ($result === null && ! $valid) {
                $buffer->seek($rollback);

                return null;
            }

            $children = $this->merge($children, $result);
        } while ($result !== null && ++$iterations);

        return $children;
    }
}
