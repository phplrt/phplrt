<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\Analysis\FailureInterval;
use Phplrt\Exception\Analysis\FailureLevel;
use Phplrt\Exception\Analysis\FailureResult;
use Phplrt\Exception\ErrorPrinter;
use Phplrt\Exception\Printer\Renderer\RendererInterface;

/**
 * The error along with the fragment of the source code it occurred in, ready
 * to be printed.
 *
 * Everything the error is described by comes from its own analysis and MAY be
 * replaced afterward, one thing at a time, while the source code is read only
 * at the moment the whole thing is turned into a string.
 *
 * @internal the object is created by the {@see ErrorPrinter}
 */
final readonly class PrintableError implements \Stringable
{
    public function __construct(
        private RendererInterface $renderer,
        /**
         * Everything that is known about the error being printed.
         */
        public FailureResult $error,
        /**
         * The error the information has been built out of.
         */
        public \Throwable $exception,
    ) {}

    /**
     * The message describing the error, or an empty string to print the error
     * without one.
     */
    public function withMessage(string $message): self
    {
        return $this->with(message: $message);
    }

    /**
     * The name identifying the error, or an empty string to print the error
     * without one.
     */
    public function withClass(string $class): self
    {
        return $this->with(class: $class);
    }

    /**
     * The severity of the error.
     */
    public function withLevel(FailureLevel $level): self
    {
        return $this->with(level: $level);
    }

    /**
     * The source the error occurred in.
     */
    public function withSource(ReadableInterface $source): self
    {
        return $this->with(source: $source);
    }

    /**
     * The place inside the source the error occurred at.
     */
    public function withPosition(PositionInterface $position): self
    {
        return $this->with(position: $position);
    }

    /**
     * The fragment of the source the error occurred in, counted in bytes from
     * the beginning of the source.
     *
     * @param int<0, max> $offset the offset the fragment starts at
     * @param int<0, max> $length the size of the fragment
     */
    public function withInterval(int $offset, int $length = 0): self
    {
        return $this->with(interval: new FailureInterval(\max(0, $offset), \max(0, $length)));
    }

    /**
     * The error covers no fragment of the source, so it points at the place
     * it occurred at.
     */
    public function withoutInterval(): self
    {
        return $this->with(interval: null);
    }

    /**
     * The renderer turning the error into a string.
     */
    public function withRenderer(RendererInterface $renderer): self
    {
        return new self($renderer, $this->error, $this->exception);
    }

    /**
     * Returns the printed representation of the error.
     *
     * @throws SourceExceptionInterface in case the data of the source cannot
     *         be read
     */
    public function render(): string
    {
        return $this->renderer->render($this->error, $this->exception);
    }

    /**
     * @throws SourceExceptionInterface in case the data of the source cannot
     *         be read
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Creates a new instance describing the error by one or more other values.
     */
    private function with(mixed ...$parameters): self
    {
        return new self($this->renderer, $this->error->with(...$parameters), $this->exception);
    }
}
