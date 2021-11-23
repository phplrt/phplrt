<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Grammar;

use Phplrt\Contracts\Buffer\BufferInterface;

class Alternation extends Production
{
    /**
     * Rule constructor.
     *
     * @param array<int|string> $sequence
     */
    public function __construct(
        public readonly array $sequence
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function reduce(BufferInterface $buffer, \Closure $reduce): mixed
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
