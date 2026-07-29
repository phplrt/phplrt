<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface as LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface as ParserRuntimeExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\ErrorInfoResult;
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

    /**
     * @return iterable<array-key, ErrorInfoResult>
     */
    private function backtrace(): iterable
    {
        $current = $this;

        do {
            if (!$current instanceof ParserRuntimeExceptionInterface
                && !$current instanceof LexerRuntimeExceptionInterface) {
                continue;
            }

            $token = $current->token;

            $length = $current instanceof ParserRuntimeExceptionInterface
                ? $current->length ?? $token->size
                : $token->size;

            yield new ErrorPrinter()
                ->print($current->source, $token->offset, $length)
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
