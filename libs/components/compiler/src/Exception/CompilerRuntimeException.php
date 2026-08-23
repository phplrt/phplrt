<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Contracts\Source\ReadableInterface;
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
         * The size of the grammar fragment the error occurred in, or
         * {@see null} in case of the error points at a position rather than
         * at a fragment.
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
                ->print($this)
                ->withSource($this->source)
                ->withInterval($this->offset, $this->length ?? 0);
        } catch (\Throwable) {
            return parent::__toString();
        }
    }
}
