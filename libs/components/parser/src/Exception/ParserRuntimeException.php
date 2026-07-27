<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\ErrorPrinter;

abstract class ParserRuntimeException extends ParserException implements
    RuntimeExceptionInterface
{
    public function __construct(
        /**
         * The source the error occurred in.
         */
        public readonly ReadableInterface $source,
        public readonly TokenInterface $token,
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function __toString(): string
    {
        if (!\class_exists(ErrorPrinter::class)) {
            return parent::__toString();
        }

        try {
            return $this->print();
        } catch (SourceExceptionInterface) {
            // The source code the error occurred in is gone, so there is
            // nothing left to show around it.
            return parent::__toString();
        }
    }

    /**
     * Returns the source code around the token along with the description
     * of the error.
     *
     * @throws SourceExceptionInterface in case the source cannot be read
     */
    private function print(): string
    {
        return (string) new ErrorPrinter()
            ->print($this->source, $this->token->offset)
            ->withEndOffset($this->token->end)
            ->withMessage($this->getMessage())
            ->withClass(static::class);
    }
}
