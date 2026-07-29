<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Compiler;

use Phplrt\Lexer\Builder\Definition\TransitionType;

/**
 * Removes the lexers that cannot be entered.
 *
 * Such a lexer is dead code: nothing would ever hand the reading over to it,
 * so it is dropped instead of being compiled.
 */
final readonly class UnreachableLexerCompilerPass implements
    LexerCompilerPassInterface
{
    public function process(LexerBuildingContext $context): void
    {
        $reachable = $this->collectReachable($context);

        foreach ($context->lexers as $name => $_) {
            if (isset($reachable[$name])) {
                continue;
            }

            unset($context->lexers[$name]);
        }
    }

    /**
     * Returns the names the token definitions of this lexer enter.
     *
     * A lexer reading a fragment of its own is entered by nothing but this
     * one, so there is nothing to walk any further.
     *
     * @return array<non-empty-string, true>
     */
    private function collectReachable(LexerBuildingContext $context): array
    {
        $result = [];

        foreach ($context->tokens as $definition) {
            $transition = $definition->transition;

            if ($transition?->type !== TransitionType::Enter) {
                continue;
            }

            /** @var non-empty-string $lexer */
            $lexer = $transition->lexer;

            $result[$lexer] = true;
        }

        return $result;
    }
}
