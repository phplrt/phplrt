<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

class LexerException extends \LogicException implements LexerExceptionInterface
{
    public static function fromInternalError(\Throwable $previous): self
    {
        return new self('An internal lexer error occurred', 0, $previous);
    }
}
