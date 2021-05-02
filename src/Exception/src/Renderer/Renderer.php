<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Exception\Renderer;

use Phplrt\Exception\RendererInterface;

abstract class Renderer implements RendererInterface
{
    /**
     * @var positive-int|0
     */
    protected int $size = 4;

    /**
     * @param positive-int|0 $size
     * @return $this
     */
    public function withSize(int $size): self
    {
        $self = clone $this;
        $self->size = \max(0, $size);

        return $self;
    }

    /**
     * @param \Throwable $e
     * @return array { 0: positive-int, 1: positive-int, 2: positive-int }
     */
    protected function getSize(\Throwable $e): array
    {
        $max = $e->getLine() + $this->size;

        return [
            \max(1, $e->getLine() - $this->size),
            $e->getLine() + $this->size,
            \strlen((string)$max)
        ];
    }
}
