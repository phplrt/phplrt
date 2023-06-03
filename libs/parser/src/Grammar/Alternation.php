<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;

class Alternation extends Production
{
    /**
     * @var array|int[]|string[]
     */
    public array $sequence = [];

    /**
     * @param array $sequence
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
