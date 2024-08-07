<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;

/**
 * @final marked as final since phplrt 3.4 and will be final since 4.0
 */
class Concatenation extends Production
{
    /**
     * @var list<array-key>
     *
     * @readonly
     *
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

    public function getTerminals(array $rules): iterable
    {
        $result = [];

        foreach ($this->sequence as $rule) {
            foreach ($rules[$rule]->getTerminals($rules) as $terminal) {
                $result[] = $terminal;
            }
        }

        return $result;
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
