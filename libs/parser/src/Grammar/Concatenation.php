<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;

class Concatenation extends Production
{
    /**
     * @var array|int[]
     */
    public array $sequence;

    /**
     * @param array $sequence
     */
    public function __construct(array $sequence)
    {
        $this->sequence = $sequence;
    }

    /**
     * {@inheritDoc}
     */
    public function reduce(BufferInterface $buffer, \Closure $reduce): ?iterable
    {
        [$revert, $children] = [$buffer->key(), []];

        foreach ($this->sequence as $rule) {
            if (($result = $reduce($rule)) === null) {
                $buffer->seek($revert);

                return null;
            }

            $children = $this->mergeWith($children, $result);
        }

        return $children;
    }
}
