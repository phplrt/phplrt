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
     * @var int
     */
    private $rule;

    /**
     * Repetition constructor.
     *
     * @param int $rule
     * @param int|float $gte
     * @param int|float $lte
     * @param \Closure|null $reducer
     */
    public function __construct(int $rule, float $gte = 0, float $lte = \INF, \Closure $reducer = null)
    {
        \assert($lte >= $gte, 'Min repetitions count must be greater or equal than max repetitions');

        parent::__construct($reducer);

        $this->rule = $rule;
        $this->gte = $gte;
        $this->lte = $lte;
    }

    /**
     * @param BufferInterface $buffer
     * @param int $type
     * @param int $offset
     * @param \Closure $reduce
     * @return iterable|null
     */
    public function reduce(BufferInterface $buffer, int $type, int $offset, \Closure $reduce): ?iterable
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

        return $this->toAst($this->merge([], $children), $type, $offset);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->rule . ' { ' . $this->gte . ' ... ' . $this->lte . ' }';
    }
}
