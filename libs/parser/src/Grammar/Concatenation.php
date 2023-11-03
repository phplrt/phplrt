<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;

class Concatenation extends Production
{
    /**
     * @var list<array-key>
     *
     * @readonly
     * @psalm-readonly-allow-private-mutation
     */
    public array $sequence = [];

    /**
     * @param list<array-key> $sequence
     */
    public function __construct(array $sequence)
    {
        $this->sequence = $sequence;
    }

    public function reduce(BufferInterface $buffer, \Closure $reduce): ?iterable
    {
        $revert = $buffer->key();
        $children = [];

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
