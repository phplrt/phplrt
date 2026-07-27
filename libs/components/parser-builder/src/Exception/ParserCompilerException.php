<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Exception;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Exception\ErrorPrinter;
use Phplrt\Parser\Builder\Definition\SourceReference;

class ParserCompilerException extends \Exception
{
    public function __construct(
        string $message,
        /**
         * The place of the source code the error refers to, in case the
         * grammar has been written rather than built by hand.
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
        $template = 'An internal error occurs while compiling the parser: %s';

        return new self(\sprintf($template, $exception->getMessage()), previous: $exception);
    }

    /**
     * @param non-empty-string $rule
     */
    public static function becauseReducerIsMalformed(
        string $rule,
        \ParseError $error,
        ?SourceReference $context = null,
    ): self {
        $template = 'The reducer of the rule %s cannot be compiled: %s';

        return new self(\sprintf($template, $rule, $error->getMessage()), $context, previous: $error);
    }
}
