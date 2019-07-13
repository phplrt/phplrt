<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Contracts\Exception\MutableException;

/**
 * Interface MutableMessageInterface
 */
interface MutableMessageInterface
{
    /**
     * @param string $message
     * @param array $args
     * @return MutableMessageInterface|$this
     */
    public function withMessage(string $message, ...$args): self;
}
