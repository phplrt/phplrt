<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\Definition\RegexTokenDefinition;
use Phplrt\Compiler\Lexer\Exception\CompilationFailedException;
use Phplrt\Compiler\Lexer\LexerBuilder;

final readonly class RegexExcessiveGreedLexerCompilerPass implements LexerCompilerPassInterface
{
    /**
     * @var list<non-empty-string>
     */
    private const array GREEDY_PATTERNS = ['.+', '.*'];

    public function process(LexerBuilder $builder): void
    {
        $greedyDefinition = null;

        foreach ($builder->tokens as $definition) {
            if (!$definition instanceof RegexTokenDefinition) {
                continue;
            }

            if ($greedyDefinition !== null) {
                throw new CompilationFailedException(\sprintf(
                    'The %s token definition does not make sense, since a more greedy %s was defined earlier',
                    $definition,
                    $greedyDefinition,
                ));
            }

            if (\in_array($definition->regex, self::GREEDY_PATTERNS, true)) {
                $greedyDefinition = $definition;
            }
        }
    }
}
