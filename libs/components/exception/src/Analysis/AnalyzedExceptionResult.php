<?php

declare(strict_types=1);

namespace Phplrt\Exception\Analysis;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Everything that is known about an error: what has been thrown, the source
 * the error refers to and the place inside that source.
 */
final readonly class AnalyzedExceptionResult
{
    public function __construct(
        /**
         * The error the information is about.
         */
        public \Throwable $exception,
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
}
