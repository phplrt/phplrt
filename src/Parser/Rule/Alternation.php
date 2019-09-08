<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Rule;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Class Alternation
 */
class Alternation extends Production
{
    /**
     * @var array|int[]|string[]
     */
    private $sequence;

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
     * @param BufferInterface $buffer
     * @param \Closure $reduce
     * @return TokenInterface|NodeInterface|null
     */
    public function reduce(BufferInterface $buffer, \Closure $reduce)
    {
        foreach ($this->sequence as $rule) {
            if (($value = $reduce($rule)) !== null) {
                return $value;
            }
        }

        return null;
    }
}
