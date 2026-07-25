<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Exception\Printer\ErrorInfo;
use Phplrt\Exception\Printer\RustStylePrinter;
use Phplrt\Exception\SnippetReader;

abstract class ParserRuntimeException extends ParserException implements
    RuntimeExceptionInterface
{
    public function __construct(
        public readonly TokenInterface $token,
        /**
         * The source code the error occurred in.
         */
        public readonly string $source,
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function __toString(): string
    {
        if (!\class_exists(RustStylePrinter::class)) {
            return parent::__toString();
        }

        return $this->print();
    }

    /**
     * Returns the source code around the token along with the description
     * of the error.
     */
    private function print(): string
    {
        $lines = new SnippetReader()
            ->createFromString($this->source, $this->token->offset, \strlen($this->token->value));

        return new RustStylePrinter()
            ->print($lines, new ErrorInfo(
                message: $this->getMessage(),
                class: static::class,
            ));
    }
}
