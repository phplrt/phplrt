<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Exception;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Exception\ErrorPrinter;
use Phplrt\Lexer\Builder\Definition\SourceReference;

class LexerCompilerException extends \Exception
{
    public function __construct(
        string $message,
        /**
         * The place of the source code the error refers to, in case the lexer
         * has been written rather than built by hand.
         */
        public readonly ?SourceReference $context = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function __toString(): string
    {
        $context = $this->context;

        if ($context === null) {
            return parent::__toString();
        }

        try {
            return (string) new ErrorPrinter()
                ->print($context->source, $context->offset, $context->length)
                ->withMessage($this->getMessage())
                ->withClass(static::class);
        } catch (SourceExceptionInterface) {
            // The source code the error refers to is gone, so there is nothing
            // left to show around it.
            return parent::__toString();
        }
    }

    public static function becauseInternalErrorOccurs(\Throwable $exception): self
    {
        $template = 'An internal error occurs while compiling the lexer: %s';

        return new self(\sprintf($template, $exception->getMessage()), previous: $exception);
    }

    /**
     * @param non-empty-string $name
     */
    public static function becauseLexerIsNotDefined(string $name, ?SourceReference $context = null): self
    {
        $template = 'The lexer "%s" the reading is handed over to has not been defined';

        return new self(\sprintf($template, $name), $context);
    }

    /**
     * @param non-empty-string $name
     */
    public static function becauseEmbeddedLexerIsMalformed(
        string $name,
        \ParseError $error,
        ?SourceReference $context = null,
    ): self {
        $template = 'The lexer "%s" cannot be compiled: %s';

        return new self(\sprintf($template, $name, $error->getMessage()), $context, previous: $error);
    }

    /**
     * @param non-empty-string $name
     */
    public static function becauseEmbeddedLexerIsInvalid(
        string $name,
        string $type,
        ?SourceReference $context = null,
    ): self {
        $template = 'The lexer "%s" must be an instance of %s, %s given';

        return new self(\sprintf($template, $name, LexerInterface::class, $type), $context);
    }
}
