<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Compiler;

use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Definition\TransitionType;

/**
 * Removes the states that cannot be entered from the initial one.
 *
 * Such states are dead code: none of their token definitions could ever be
 * used, so they are dropped instead of being compiled into the lexer.
 *
 * Note: Reachability is transitive, so a state entered ONLY from an already
 *       unreachable state is removed as well.
 */
final readonly class UnreachableStateLexerCompilerPass implements
    LexerCompilerPassInterface
{
    public function process(LexerBuildingContext $context): void
    {
        $reachable = $this->collectReachable($context);

        foreach ($context->states as $name => $_) {
            if (isset($reachable[$name])) {
                continue;
            }

            unset($context->states[$name]);
        }

        foreach ($context->embeddedStates as $name => $_) {
            if (isset($reachable[$name])) {
                continue;
            }

            unset($context->embeddedStates[$name]);
        }
    }

    /**
     * Walks the transitions starting from the initial state.
     *
     * @return array<non-empty-string, true>
     */
    private function collectReachable(LexerBuildingContext $context): array
    {
        $reachable = [];
        $queue = $this->getEnteredStates($context->tokens);

        while ($queue !== []) {
            $name = \array_pop($queue);

            if (isset($reachable[$name])) {
                continue;
            }

            $reachable[$name] = true;

            $state = $context->states[$name] ?? null;

            /**
             * A state read by a lexer of its own has no transitions to walk,
             * while an undefined one is not this pass's business: it is
             * reported by the {@see TransitionValidationLexerCompilerPass}.
             */
            if ($state === null) {
                continue;
            }

            foreach ($this->getEnteredStates($state) as $next) {
                $queue[] = $next;
            }
        }

        return $reachable;
    }

    /**
     * @param array<array-key, TokenDefinition> $definitions
     * @return list<non-empty-string>
     */
    private function getEnteredStates(array $definitions): array
    {
        $result = [];

        foreach ($definitions as $definition) {
            $transition = $definition->transition;

            if ($transition?->type !== TransitionType::Enter) {
                continue;
            }

            /** @var non-empty-string $state */
            $state = $transition->state;

            $result[] = $state;
        }

        return $result;
    }
}
