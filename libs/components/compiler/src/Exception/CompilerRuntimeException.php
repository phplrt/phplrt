<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface as LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface as ParserRuntimeExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\ErrorInfoResult;
use Phplrt\Exception\ErrorPrinter;

/**
 * An error of the grammar being compiled, at the place the grammar says it.
 *
 * A grammar is read into a tree recording where every element is written
 * instead of the tokens it has been read from, so such an error points at a
 * fragment of the grammar by the positions of its own rather than by a token.
 */
abstract class CompilerRuntimeException extends CompilerException
{
    public function __construct(
        /**
         * The grammar file the error occurred in.
         */
        public readonly ReadableInterface $source,
        /**
         * The position of the grammar file the error occurred at.
         *
         * @var int<0, max>
         */
        public readonly int $offset,
        string $message,
        /**
         * The position the error ends at, or {@see null} in case of the error
         * ends where it starts.
         *
         * @var int<0, max>|null
         */
        public readonly ?int $end = null,
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
     * Describes every error of the chain that points at a fragment of a source
     * code, in the order the errors have been thrown.
     *
     * A grammar is read in three layers and every one of them says where it
     * has failed in its own way: a lexer fails on a token, a parser fails on a
     * token and may span as far as the rule it has failed on, and a compiler
     * fails on a fragment of a grammar that has been read from no token at
     * all.
     *
     * @return iterable<array-key, ErrorInfoResult>
     */
    private function backtrace(): iterable
    {
        $current = $this;

        do {
            if ($current instanceof self) {
                yield self::describe($current, $current->source, $current->offset, $current->end ?? $current->offset);

                continue;
            }

            if ($current instanceof ParserRuntimeExceptionInterface) {
                $token = $current->token;

                yield self::describe($current, $current->source, $token->offset, $current->end ?? $token->end);

                continue;
            }

            if ($current instanceof LexerRuntimeExceptionInterface) {
                $token = $current->token;

                yield self::describe($current, $current->source, $token->offset, $token->end);
            }
        } while (($current = $current->getPrevious()) !== null);
    }

    /**
     * @param int<0, max> $offset
     * @param int<0, max> $end
     */
    private static function describe(
        \Throwable $error,
        ReadableInterface $source,
        int $offset,
        int $end,
    ): ErrorInfoResult {
        return new ErrorPrinter()
            ->print($source, $offset)
            ->withEndOffset($end)
            ->withMessage($error->getMessage())
            ->withClass($error::class);
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
            // The grammar the error occurred in is gone, so there is nothing
            // left to show around it.
            return parent::__toString();
        }
    }
}
