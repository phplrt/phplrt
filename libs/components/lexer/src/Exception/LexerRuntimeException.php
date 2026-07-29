<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\ErrorInfoResult;
use Phplrt\Exception\ErrorPrinter;

abstract class LexerRuntimeException extends LexerException implements
    RuntimeExceptionInterface
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
        parent::__construct(
            message: $message,
            code: $code,
            previous: $previous,
        );
    }

    /**
     * @return iterable<array-key, ErrorInfoResult>
     */
    private function backtrace(): iterable
    {
        $current = $this;

        do {
            if (!$current instanceof RuntimeExceptionInterface) {
                continue;
            }

            $token = $current->token;

            yield new ErrorPrinter()
                ->print($current->source, $token->offset, $token->size)
                ->withMessage($current->getMessage())
                ->withClass($current::class);
        } while (($current = $current->getPrevious()) !== null);
    }

    public function __toString(): string
    {
        if (!\class_exists(ErrorPrinter::class)) {
            return parent::__toString();
        }

        try {
            return \implode("\n", [
                ...$this->backtrace(),
                \sprintf('  thrown in %s on line %d', $this->file, $this->line),
                $this->getTraceAsString(),
            ]);
        } catch (\Throwable) {
            // The source code the error occurred in is gone, so there is
            // nothing left to show around it.
            return parent::__toString();
        }
    }
}
