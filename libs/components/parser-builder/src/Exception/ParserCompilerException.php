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

        try {
            $result = new ErrorPrinter()
                ->print($this);

            if ($context !== null) {
                $result = $result
                    ->withSource($context->source)
                    ->withInterval($context->offset, $context->length);
            }

            return (string) $result;
        } catch (SourceExceptionInterface) {
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
