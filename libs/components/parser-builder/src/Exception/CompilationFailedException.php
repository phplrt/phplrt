<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Exception;

use Phplrt\Compiler\Parser\Definition\Definition;
use Phplrt\Compiler\Parser\Definition\RuleDefinition;

class CompilationFailedException extends ParserCompilerException
{
    public function __construct(
        public Definition $definition,
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function becauseRuleIsEmpty(RuleDefinition $rule): self
    {
        $template = 'Rule %s must refer to at least one rule';

        return new self($rule, \sprintf($template, $rule));
    }

    public static function becauseTokenIsUnknown(RuleDefinition $rule): self
    {
        $template = 'Rule %s refers to the token, which is not recognized by the lexer';

        return new self($rule, \sprintf($template, $rule));
    }
}
