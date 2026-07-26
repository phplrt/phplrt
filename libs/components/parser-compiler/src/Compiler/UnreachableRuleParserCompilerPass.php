<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Compiler;

use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\Definition\RuleDefinition;
use Phplrt\Compiler\Parser\ParserBuilder;

/**
 * Removes the rules that cannot be reached from the initial one.
 *
 * Such rules are dead code: none of them could ever be recognized, so they are
 * dropped instead of being compiled into the parser.
 *
 * Note: Reachability is transitive, so a rule referred to ONLY by an already
 *       unreachable rule is removed as well.
 */
final readonly class UnreachableRuleParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuilder $builder, LexerBuilderResult $lexer): void
    {
        $initial = $builder->findInitialRule();

        if ($initial === null) {
            return;
        }

        $reachable = $this->collectReachable($initial);
        $unreachable = [];

        foreach ($builder->rules as $rule) {
            if ($reachable->offsetExists($rule)) {
                continue;
            }

            $unreachable[] = $rule;
        }

        foreach ($unreachable as $rule) {
            $builder->removeRule($rule);
        }
    }

    /**
     * Walks the references starting from the initial rule.
     *
     * @return \SplObjectStorage<RuleDefinition, null>
     */
    private function collectReachable(RuleDefinition $initial): \SplObjectStorage
    {
        /** @var \SplObjectStorage<RuleDefinition, null> $reachable */
        $reachable = new \SplObjectStorage();
        $queue = [$initial];

        while ($queue !== []) {
            $rule = \array_pop($queue);

            if ($reachable->offsetExists($rule)) {
                continue;
            }

            $reachable->offsetSet($rule);

            foreach ($rule->children as $child) {
                $queue[] = $child;
            }
        }

        return $reachable;
    }
}
