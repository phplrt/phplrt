<?php

declare(strict_types=1);

namespace Phplrt\Exception\Analysis;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Everything that is known about an error: what it says about itself, the
 * source it refers to and the place inside that source.
 */
final readonly class FailureResult
{
    public function __construct(
        /**
         * The name identifying the error, like the class of an exception, or
         * an empty string in case the error is known under no name.
         */
        public string $class,
        /**
         * The message describing the error, or an empty string in case the
         * error describes itself by nothing.
         */
        public string $message,
        /**
         * The source the error occurred in, which is the file the error has
         * been thrown from in case the error refers to no source of its own.
         */
        public ReadableInterface $source,
        /**
         * The place inside the source the error occurred at, the column of
         * which is the beginning of the line in case nothing but the line is
         * known about the error.
         */
        public PositionInterface $position,
        /**
         * The severity of the error.
         */
        public FailureLevel $level = FailureLevel::DEFAULT,
        /**
         * The fragment of the source the error occurred in, or {@see null} in
         * case the error tells nothing about the size of it.
         */
        public ?FailureInterval $interval = null,
        /**
         * The information about the error that has led to this one, or
         * {@see null} in case there is none.
         */
        public ?self $previous = null,
    ) {}

    /**
     * Creates a new instance with one or more overrides.
     *
     * ```
     * $result = $result->with(
     *     source: new FileSource(...),
     *     position: new Position(),
     * );
     * ```
     */
    public function with(mixed ...$parameters): self
    {
        /** @phpstan-ignore argument.type */
        return new self(...[...\get_object_vars($this), ...$parameters]);
    }
}
