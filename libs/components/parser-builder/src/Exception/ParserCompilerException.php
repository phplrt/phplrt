<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Exception;

class ParserCompilerException extends \Exception
{
    public static function becauseInternalErrorOccurs(\Throwable $exception): self
    {
        $template = 'An internal error occurs while compiling the parser: %s';

        return new self(\sprintf($template, $exception->getMessage()), 0, $exception);
    }
}
