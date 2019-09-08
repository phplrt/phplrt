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
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Buffer\BufferInterface;

/**
 * Class Optional
 */
class Optional extends Production
{
    /**
     * @var int|string
     */
    private $rule;

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
     * @param BufferInterface $buffer
     * @param \Closure $reduce
     * @return NodeInterface|TokenInterface|array|null
     */
    public function reduce(BufferInterface $buffer, \Closure $reduce)
    {
        $rollback = $buffer->key();

        if ($result = $reduce($this->rule)) {
            return $result;
        }

        $buffer->seek($rollback);

        return [];
    }
}
