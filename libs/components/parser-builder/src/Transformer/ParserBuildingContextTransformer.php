<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Transformer;

use Phplrt\Parser\Builder\Compiler\ParserBuildingContext;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\ParserBuilder;

/**
 * Copies the grammar of the builder into the context the compiler passes work
 * on, so that a pass rewriting the rules does not reach the builder.
 */
final readonly class ParserBuildingContextTransformer
{
    public function transform(ParserBuilder $builder): ParserBuildingContext
    {
        /** @var \SplObjectStorage<RuleDefinition, RuleDefinition> $copies */
        $copies = new \SplObjectStorage();

        $initial = $builder->initial;

        $initial?->clone($copies);

        foreach ($builder->rules as $rule) {
            $rule->clone($copies);
        }

        /**
         * A copy is registered the moment the rule is reached, so the map is
         * already ordered the way the grammar is walked.
         */
        $rules = [];

        foreach ($copies as $rule) {
            $rules[] = $copies[$rule];
        }

        return new ParserBuildingContext(
            initial: $initial === null ? null : $copies[$initial],
            rules: $rules,
        );
    }
}
