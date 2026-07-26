<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\Definition\RegexTokenDefinition;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\Exception\CompilationFailedException;

/**
 * Checks that the regular expression is specified correctly.
 */
final readonly class RegexValidationLexerCompilerPass implements
    LexerCompilerPassInterface
{
    public function process(LexerBuildingContext $context): void
    {
        $this->validateOrFail($context->tokens);

        foreach ($context->states as $state) {
            $this->validateOrFail($state);
        }
    }

    /**
     * @param array<array-key, TokenDefinition> $definitions
     * @throws CompilationFailedException
     */
    private function validateOrFail(array $definitions): void
    {
        foreach ($definitions as $definition) {
            if (!$definition instanceof RegexTokenDefinition) {
                continue;
            }

            $this->validateRegexOrFail($definition->regex, $definition);
        }
    }

    /**
     * @param non-empty-string $regex
     * @throws CompilationFailedException in case of token compilation failure
     */
    private function validateRegexOrFail(string $regex, TokenDefinition $definition): void
    {
        try {
            \error_clear_last();
            \set_error_handler(self::emptyErrorHandler(...));

            @\preg_match($this->compile($regex), '');

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

    private static function emptyErrorHandler(int $id, string $error, string $file, int $line): bool
    {
        // NO-OP
        return false;
    }

    /**
     * @param non-empty-string $regex
     * @return non-empty-string
     */
    private function compile(string $regex): string
    {
        return \sprintf('/%s/u', \addcslashes($regex, '/#'));
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
