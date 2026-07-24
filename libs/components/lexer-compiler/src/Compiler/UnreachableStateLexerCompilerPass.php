<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\Definition\TransitionType;
use Phplrt\Compiler\Lexer\LexerBuilder;

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
    public function process(LexerBuilder $builder): void
    {
        $reachable = $this->collectReachable($builder);

        foreach ($builder->states as $name => $_) {
            if (isset($reachable[$name])) {
                continue;
            }

            $builder->removeState($name);
        }
    }

    /**
     * Walks the transitions starting from the initial state.
     *
     * @return array<non-empty-string, true>
     */
    private function collectReachable(LexerBuilder $builder): array
    {
        $reachable = [];
        $queue = $this->getEnteredStates($builder->tokens);

        while ($queue !== []) {
            $name = \array_pop($queue);

            if (isset($reachable[$name])) {
                continue;
            }

            $reachable[$name] = true;

            $state = $builder->states[$name] ?? null;

            /**
             * An undefined target is not this pass's business: it is reported
             * by the {@see TransitionValidationLexerCompilerPass}.
             */
            if ($state === null) {
                continue;
            }

            foreach ($this->getEnteredStates($state->tokens) as $next) {
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
