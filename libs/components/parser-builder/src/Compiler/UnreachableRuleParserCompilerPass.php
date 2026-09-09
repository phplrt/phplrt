<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Definition\RuleDefinition;

/**
 * Removes the rules that cannot be reached from the kept ones.
 *
 * Such rules are dead code: none of them could ever be recognized, so they are
 * dropped instead of being compiled into the parser.
 *
 * Note: Reachability is transitive, so a rule referred to ONLY by an already
 *       unreachable rule is removed as well.
 *
 * @readonly
 */
final class UnreachableRuleParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        $reachable = $context->collectReachableRules();

        if ($reachable === []) {
            return;
        }

        $this->report($context, $reachable);

        $context->rules = $reachable;
    }

    /**
     * @param non-empty-list<RuleDefinition> $reachable
     */
    private function report(ParserBuildingContext $context, array $reachable): void
    {
        /** @var \SplObjectStorage<RuleDefinition, null> $known */
        $known = new \SplObjectStorage();

        foreach ($reachable as $rule) {
            $known->offsetSet($rule);
        }

        foreach ($context->rules as $rule) {
            if ($known->offsetExists($rule)) {
                continue;
            }

            $context->logger->info('Rule {rule} is removed, since the analysis cannot reach it', [
                'rule' => (string) $rule,
            ]);
        }
    }
}
