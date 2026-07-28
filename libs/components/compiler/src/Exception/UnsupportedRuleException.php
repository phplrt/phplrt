<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Parser\Grammar\RuleInterface;

/**
 * The grammar is written of a rule the generator knows nothing about.
 */
final class UnsupportedRuleException extends GeneratorException
{
    public static function becauseRuleCannotBeGenerated(
        int $rule,
        RuleInterface $definition,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('The rule #%d is written as %s, which cannot be generated', $rule, $definition::class);

        return new self($message, previous: $previous);
    }
}
