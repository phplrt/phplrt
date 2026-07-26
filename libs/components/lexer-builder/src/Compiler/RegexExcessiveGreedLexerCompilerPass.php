<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\Definition\RegexTokenDefinition;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\Exception\CompilationFailedException;

/**
 * Checks that the lexer does not contain "unmatchable" regular expressions
 */
final readonly class RegexExcessiveGreedLexerCompilerPass implements
    LexerCompilerPassInterface
{
    /**
     * @var list<non-empty-string>
     */
    private const array WIDE_PATTERNS = ['.+', '.*'];

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
        $wideTokenDefinition = null;

        foreach ($definitions as $definition) {
            if (!$definition instanceof RegexTokenDefinition) {
                continue;
            }

            if ($wideTokenDefinition !== null) {
                throw new CompilationFailedException($definition, \sprintf(
                    'Token definition %s does not make sense, since higher priority %s was defined earlier',
                    $definition,
                    $wideTokenDefinition,
                ));
            }

            if (\in_array($definition->regex, self::WIDE_PATTERNS, true)) {
                $wideTokenDefinition = $definition;
            }
        }
    }
}
