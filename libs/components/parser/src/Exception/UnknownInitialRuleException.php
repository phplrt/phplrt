<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

final class UnknownInitialRuleException extends ParserException
{
    public static function becauseRuleIsNotDefined(int $rule, ?\Throwable $previous = null): self
    {
        $message = \sprintf('Cannot start the analysis at rule #%d, which is not defined', $rule);

        return new self($message, 0, $previous);
    }
}
