<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Grammar;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\BufferInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Class Alternation
 */
class Alternation extends Production
{
    /**
     * @var array|int[]|string[]
     */
    public $sequence;

    /**
     * Rule constructor.
     *
     * @param array $sequence
     */
    public function __construct(array $sequence)
    {
        $this->sequence = $sequence;
    }

    /**
     * @return array
     */
    public function getConstructorArguments(): array
    {
        return [$this->sequence];
    }

    /**
     * @param BufferInterface $buffer
     * @param \Closure $reduce
     * @return TokenInterface|NodeInterface|null
     */
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
