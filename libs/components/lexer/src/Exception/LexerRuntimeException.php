<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

abstract class LexerRuntimeException extends LexerException implements RuntimeExceptionInterface
{
    public function __construct(
        /**
         * Gets the source object in which the error occurred.
         */
        public readonly ReadableInterface $source,
        /**
         * Gets the token on which the error occurred.
         */
        public readonly TokenInterface $token,
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
