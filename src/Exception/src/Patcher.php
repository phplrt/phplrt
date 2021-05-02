<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Position\Position;
use Phplrt\Source\File;

final class Patcher
{
    /**
     * @psalm-var positive-int|0|null
     */
    private ?int $offset = null;

    /**
     * @var positive-int|null
     */
    private ?int $line = null;

    /**
     * @var ReadableInterface|null
     */
    private ?ReadableInterface $source = null;

    /**
     * @var \Throwable
     */
    private \Throwable $exception;

    /**
     * @param \Throwable $exception
     */
    public function __construct(\Throwable $exception)
    {
        $this->exception = $exception;

        if (\is_file($this->exception->getFile())) {
            $this->source = File::fromPathname($this->exception->getFile());
            $this->line = $this->exception->getLine();
        }
    }

    /**
     * @return void
     */
    private function patch(): void
    {
        if (
            // Position not defined
            ($this->line === null && $this->offset === null) ||
            // Source not defined
            $this->source === null
        ) {
            return;
        }

        $position = $this->line !== null
            ? Position::fromLineAndColumn($this->source, $this->line)
            : Position::fromOffset($this->source, $this->offset)
        ;

        $this->getPatcher($this->source, $position)
            ->call($this->exception)
        ;
    }

    /**
     * @param ReadableInterface $source
     * @param PositionInterface $position
     * @return \Closure
     */
    private function getPatcher(ReadableInterface $source, PositionInterface $position): \Closure
    {
        return function() use ($source, $position) {
            /**
             * @var \Exception $this
             * @psalm-suppress InaccessibleProperty
             */
            if ($source instanceof FileInterface) {
                $this->file = $source->getPathname();
                $this->line = $position->getLine();
            }
        };
    }

    /**
     * @param ReadableInterface $source
     * @return $this
     */
    public function withSource(ReadableInterface $source): self
    {
        $self = clone $this;
        $self->source = $source;

        $self->patch();

        return $self;
    }

    /**
     * @param TokenInterface $token
     * @return $this
     */
    public function withToken(TokenInterface $token): self
    {
        $self = clone $this;
        $self->line = null;
        $self->offset = $token->getOffset();

        $self->patch();

        return $self;
    }

    /**
     * @param PositionInterface $position
     * @return $this
     */
    public function withPosition(PositionInterface $position): self
    {
        $self = clone $this;
        $self->line = null;
        $self->offset = $position->getOffset();

        $self->patch();

        return $self;
    }

    /**
     * @param positive-int $line
     * @return $this
     */
    public function withLine(int $line): self
    {
        $self = clone $this;
        $self->line = \max(Position::MIN_LINE, $line);
        $self->offset = null;

        $self->patch();

        return $self;
    }

    /**
     * @param positive-int|0 $offset
     * @return $this
     */
    public function withOffset(int $offset): self
    {
        $self = clone $this;
        $self->line = null;
        $self->offset = \max(Position::MIN_OFFSET, $offset);

        $self->patch();

        return $self;
    }
}
