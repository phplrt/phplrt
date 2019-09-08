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
 * Interface RuleInterface
 */
interface RuleInterface
{
    /**
     * @param BufferInterface $buffer
     * @param \Closure $reduce
     * @return mixed|null
     */
    public function reduce(BufferInterface $buffer, \Closure $reduce);
}
