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
 * Class Concatenation
 */
class Concatenation extends Production
{
    /**
     * @var array|int[]
     */
    private $sequence;

    /**
     * Rule constructor.
     *
     * @param array $sequence
     * @param \Closure $reducer|null
     */
    public function __construct(array $sequence, \Closure $reducer = null)
    {
        $this->sequence = $sequence;

        parent::__construct($reducer);
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
        [$revert, $children] = [$buffer->key(), []];

        foreach ($this->sequence as $rule) {
            if (($result = $reduce($rule)) === null) {
                $buffer->seek($revert);

                return null;
            }

            $children = $this->merge($children, $result);
        }

        return $this->toAst($children, $offset, $type);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return '[' . \implode(', ', $this->sequence) . ']';
    }
}
