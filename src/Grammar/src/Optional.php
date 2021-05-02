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
     * @var int|string
     */
    public $rule;

    /**
     * Optional constructor.
     *
     * @param int|string $rule
     */
    public function __construct($rule)
    {
        $this->rule = $rule;
    }

    /**
     * {@inheritDoc}
     */
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
