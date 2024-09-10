<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;

/**
 * @final marked as final since phplrt 3.4 and will be final since 4.0
 */
class Optional extends Production
{
    public function __construct(
        /**
         * @readonly
         * @psalm-readonly-allow-private-mutation
         * @var array-key
         */
        public string|int $rule,
    ) {}

    public function getTerminals(array $rules): iterable
    {
        if (!isset($rules[$this->rule])) {
            return [];
        }

        return $rules[$this->rule]->getTerminals($rules);
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
