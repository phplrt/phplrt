<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Rule;

use Phplrt\Parser\Buffer\BufferInterface;

/**
 * Class Optional
 */
class Optional extends Production
{
    /**
     * @var int
     */
    private $rule;

    /**
     * Optional constructor.
     *
     * @param int $rule
     * @param \Closure|null $reducer
     */
    public function __construct(int $rule, \Closure $reducer = null)
    {
        parent::__construct($reducer);

        $this->rule = $rule;
    }

    /**
     * @param BufferInterface $buffer
     * @param int $type
     * @param int $offset
     * @param \Closure $reduce
     * @return iterable|null
     */
    public function reduce(BufferInterface $buffer, int $type, int $offset, \Closure $reduce): ?iterable
    {
        $rollback = $buffer->key();

        if ($result = $reduce($this->rule)) {
            return $this->toAst($this->merge([], $result), $offset, $type);
        }

        $buffer->seek($rollback);

        return [];
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->rule . '?';
    }
}
