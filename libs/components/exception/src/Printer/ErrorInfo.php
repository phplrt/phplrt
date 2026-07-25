<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer;

/**
 * The information about the error that cannot be derived from the source code
 * lines describing it.
 */
final readonly class ErrorInfo
{
    public function __construct(
        /**
         * The message describing the error.
         */
        public ?string $message = null,
        /**
         * The pathname of the file containing the source code.
         */
        public ?string $pathname = null,
        /**
         * The name identifying the error, like the class of an exception.
         */
        public ?string $class = null,
        /**
         * The severity of the error.
         */
        public Level $level = Level::Error,
    ) {}
}
