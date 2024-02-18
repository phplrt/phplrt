<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

class ParserException extends \LogicException implements ParserExceptionInterface
{
    public static function fromInternalError(\Throwable $previous): self
    {
        return new self('An internal parser error occurred', 0, $previous);
    }
}
