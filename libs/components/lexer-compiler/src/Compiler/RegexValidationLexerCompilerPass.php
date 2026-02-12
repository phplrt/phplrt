<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\Definition\RegexTokenDefinition;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\Exception\CompilationFailedException;
use Phplrt\Compiler\Lexer\LexerBuilder;

final readonly class RegexValidationLexerCompilerPass implements LexerCompilerPassInterface
{
    public function process(LexerBuilder $builder): void
    {
        foreach ($builder->tokens as $definition) {
            if (!$definition instanceof RegexTokenDefinition) {
                continue;
            }

            $this->validateOrFail($definition->regex, $definition);
        }
    }

    private static function emptyErrorHandler(int $id, string $error, string $file, int $line): bool
    {
        // NO-OP
        return false;
    }

    /**
     * @param non-empty-string $regex
     *
     * @throws CompilationFailedException in case of token compilation failure
     */
    private function validateOrFail(string $regex, TokenDefinition $definition): void
    {
        \error_clear_last();
        \set_error_handler(self::emptyErrorHandler(...));

        @\preg_match($this->compile($regex), '');

        if (($error = \error_get_last()) === null) {
            return;
        }

        \restore_error_handler();
        \error_clear_last();

        throw new CompilationFailedException($this->formatException($error['message'], (string) $definition));
    }

    /**
     * @param non-empty-string $regex
     *
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
