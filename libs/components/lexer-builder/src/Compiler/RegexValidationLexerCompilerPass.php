<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Compiler;

use Phplrt\Lexer\Builder\Definition\RegexModifier;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Exception\CompilationFailedException;
use Phplrt\Lexer\Builder\Internal\PatternFormatter;

/**
 * Checks that the regular expression is specified correctly.
 *
 * @readonly
 */
final class RegexValidationLexerCompilerPass implements
    LexerCompilerPassInterface
{
    public function process(LexerBuildingContext $context): void
    {
        $this->validateOrFail($context->tokens, $context->flags);
    }

    /**
     * @param array<array-key, TokenDefinition> $definitions
     * @param array<array-key, RegexModifier> $flags
     * @throws CompilationFailedException
     */
    private function validateOrFail(array $definitions, array $flags): void
    {
        foreach ($definitions as $definition) {
            if (!$definition instanceof RegexTokenDefinition) {
                continue;
            }

            $this->validateRegexOrFail($definition->regex, $flags, $definition);
        }
    }

    /**
     * @param non-empty-string $regex
     * @param array<array-key, RegexModifier> $flags
     * @throws CompilationFailedException in case of token compilation failure
     */
    private function validateRegexOrFail(string $regex, array $flags, TokenDefinition $definition): void
    {
        try {
            \error_clear_last();
            \set_error_handler(self::emptyErrorHandler(...));

            @\preg_match(PatternFormatter::formatPattern(PatternFormatter::formatExpression($regex, $flags), $flags), '');

            if (($error = \error_get_last()) === null) {
                return;
            }
        } finally {
            \restore_error_handler();
            \error_clear_last();
        }

        throw new CompilationFailedException($definition, $this->formatException(
            message: $error['message'],
            name: (string) $definition,
        ));
    }

    private static function emptyErrorHandler(): bool
    {
        // NO-OP
        return false;
    }

    /**
     * @return non-empty-string
     */
    private function formatException(string $message, string $name): string
    {
        $suffix = \sprintf(' in %s token definition', $name);

        $message = \str_replace('Compilation failed: ', '', $message);
        $message = (string) \preg_replace('/([\w_]+\(\):\h+)/', '', $message);
        $message = (string) \preg_replace('/\h*at\h+offset\h+\d+/', '', $message);

        return \ucfirst($message) . $suffix;
    }
}
