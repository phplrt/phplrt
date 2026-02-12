<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\Exception\CompilationFailedException;
use Phplrt\Compiler\Lexer\LexerBuilder;

final readonly class TokenNameValidationLexerCompilerPass implements LexerCompilerPassInterface
{
    /**
     * @var list<non-empty-string>
     */
    private const array ALLOWED_NAME_CHARS = ['_'];

    public function process(LexerBuilder $builder): void
    {
        foreach ($builder->tokens as $definition) {
            if ($definition->name === null) {
                continue;
            }

            /** @phpstan-ignore-next-line Additional assertion */
            if ($definition->name === '') {
                throw new CompilationFailedException('Token name cannot be empty');
            }

            if (!\ctype_alpha($definition->name[0])) {
                throw new CompilationFailedException(\sprintf(
                    'Token %s name must start with an ASCII letter',
                    $definition,
                ));
            }

            $nameWithoutAllowed = \str_replace(self::ALLOWED_NAME_CHARS, '', $definition->name);

            if (!\ctype_alnum($nameWithoutAllowed)) {
                throw new CompilationFailedException(\sprintf(
                    'Token %s must contain only ASCII letters, digits and %s chars',
                    $definition,
                    \implode(', ', self::ALLOWED_NAME_CHARS),
                ));
            }
        }
    }
}
