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

class Optional extends Production
{
    /**
     * @var int|non-empty-string
     */
    public int|string $rule;

    /**
     * Optional constructor.
     *
     * @param int|non-empty-string $rule
     */
    public function __construct(int|string $rule)
    {
        $this->rule = $rule;
    }

    /**
     * {@inheritDoc}
     */
    public function reduce(BufferInterface $buffer, \Closure $reduce): mixed
    {
        $rollback = $buffer->key();

        if (($result = $reduce($this->rule)) !== null) {
            return $result;
        }

        $buffer->seek($rollback);

        return [];
    }
}
