<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Trace;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Trace\InvocationInterface;

abstract class Invocation implements InvocationInterface
{
    /**
     * @param ReadableInterface $source
     * @param PositionInterface $position
     */
    public function __construct(
        protected readonly ReadableInterface $source,
        protected readonly PositionInterface $position,
    ) {
    }

    /**
     * @return \Reflector
     */
    abstract public function getReflection(): \Reflector;

    /**
     * {@inheritDoc}
     */
    public function getSource(): ReadableInterface
    {
        return $this->source;
    }

    /**
     * {@inheritDoc}
     */
    public function getOffset(): int
    {
        return $this->position->getOffset();
    }

    /**
     * {@inheritDoc}
     */
    public function getLine(): int
    {
        return $this->position->getLine();
    }

    /**
     * {@inheritDoc}
     */
    public function getColumn(): int
    {
        return $this->position->getColumn();
    }
}
