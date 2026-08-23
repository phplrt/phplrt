<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface as ParserRuntimeExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\ErrorPrinter;

abstract class ParserRuntimeException extends ParserException implements
    ParserRuntimeExceptionInterface
{
    public function __construct(
        /**
         * The source the error occurred in.
         */
        public readonly ReadableInterface $source,
        public readonly TokenInterface $token,
        string $message,
        /**
         * The size of the fragment the error occurred in, or {@see null} in
         * case of the error is as large as the token itself.
         *
         * @var int<0, max>|null
         */
        public readonly ?int $length = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            code: $code,
            previous: $previous,
        );
    }

    public function __toString(): string
    {
        try {
            return (string) new ErrorPrinter()
                ->print($this);
        } catch (\Throwable) {
            return parent::__toString();
        }
    }
}
