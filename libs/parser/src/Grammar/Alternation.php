<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;

class Alternation extends Production
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

    public function reduce(BufferInterface $buffer, \Closure $reduce)
    {
        $rollback = $buffer->key();

        foreach ($this->sequence as $rule) {
            if (($result = $reduce($rule)) !== null) {
                return $result;
            }

            $buffer->seek($rollback);
        }

        return null;
    }
}
