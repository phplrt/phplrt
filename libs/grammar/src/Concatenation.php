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

class Concatenation extends Production
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
