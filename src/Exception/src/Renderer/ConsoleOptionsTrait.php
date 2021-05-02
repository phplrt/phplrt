<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Exception\Renderer;

use SebastianBergmann\Environment\Console;

trait ConsoleOptionsTrait
{
    /**
     * @var bool
     */
    protected bool $colors;

    /**
     * @var positive-int
     */
    protected int $columns;

    /**
     * @var bool
     */
    protected bool $utf = true;

    /**
     * @var positive-int|0
     */
    protected int $offset = 2;

    /**
     * @return bool
     */
    private function getEnvColorsSupport(): bool
    {
        return (new Console())->hasColorSupport();
    }

    /**
     * @return positive-int
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    private function getEnvNumberOfColumns(): int
    {
        return (new Console())->getNumberOfColumns();
    }

    /**
     * @return bool
     */
    public function hasColorsSupport(): bool
    {
        return $this->colors;
    }

    /**
     * @param bool|null $colors
     * @return $this
     */
    public function withColorsSupport(bool $colors = null): self
    {
        $self = clone $this;
        $self->colors = $colors ?? $this->getEnvColorsSupport();

        return $self;
    }

    /**
     * @return positive-int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param positive-int|0 $offset
     * @return $this
     */
    public function withOffset(int $offset = 2): self
    {
        $self = clone $this;
        $self->offset = \max(0, $offset);

        return $self;
    }

    /**
     * @return bool
     */
    public function hasUtfSupport(): bool
    {
        return $this->utf;
    }

    /**
     * @param bool $utf
     * @return $this
     */
    public function withUtfSupport(bool $utf = true): self
    {
        $self = clone $this;
        $self->utf = $utf;

        return $self;
    }

    /**
     * @return positive-int
     */
    public function getNumberOfColumns(): int
    {
        return $this->columns;
    }

    /**
     * @param positive-int|null $columns
     * @return $this
     */
    public function withNumberOfColumns(int $columns = null): self
    {
        $self = clone $this;
        $self->columns = $columns ?? $this->getEnvNumberOfColumns();

        return $self;
    }
}
