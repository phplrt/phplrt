<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;

class Optional extends Production
{
    /**
     * @var array-key
     *
     * @readonly
     * @psalm-readonly-allow-private-mutation
     */
    public $rule;

    /**
     * @param array-key $rule
     */
    public function __construct($rule)
    {
        $this->rule = $rule;
    }

    public function reduce(BufferInterface $buffer, \Closure $reduce)
    {
        $rollback = $buffer->key();

        if (($result = $reduce($this->rule)) !== null) {
            return $result;
        }

        $buffer->seek($rollback);

        return [];
    }
}
