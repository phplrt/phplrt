<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Analysis;

use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Predicate;

/**
 * Describes which elements of a sequence are read in place.
 *
 * An optional element is entered on every reading of the sequence only to be
 * given up at once most of the time, and a rule that is given up before it
 * reads anything leaves nothing behind. Such an element is written down as
 * the rule it wraps, negated, so the sequence reads the rule itself and goes
 * on either way, without entering the optional at all.
 *
 * A negative predicate holds on every token the rule it looks for may not
 * begin with, so it is entered only to be found holding most of the time as
 * well. Such an element is written down as the predicate itself, negated, so
 * the sequence asks the rule the predicate looks for before entering it.
 *
 * An optional or a predicate that becomes a node of the tree is left as it
 * is: what it leaves behind is exactly the node. An optional of a predicate is
 * left as it is too, since the predicate it wraps would be taken for one
 * standing in the sequence on its own.
 *
 * @readonly
 */
final class SequencePredictionConstructionParserAnalysisPass implements
    ParserAnalysisPassInterface
{
    public function process(ParserResultContext $context): void
    {
        $grammar = $context->grammar;
        $kept = $context->kept;
        $startPrediction = $context->startPrediction;

        $result = [];
        $inlined = 0;

        foreach ($grammar as $id => $rule) {
            if (!$rule instanceof Concatenation) {
                continue;
            }

            $elements = [];
            $changed = false;

            foreach ($rule->ruleIds as $inner) {
                $definition = $grammar[$inner] ?? null;

                if ($definition instanceof Optional
                    && !($kept[$inner] ?? true)
                    && !($grammar[$definition->ruleId] ?? null) instanceof Predicate
                ) {
                    $elements[] = -$definition->ruleId - 1;
                    $changed = true;
                    ++$inlined;

                    continue;
                }

                // A rule that may begin with any token is no help in telling
                // whether the predicate holds
                if ($definition instanceof Predicate
                    && !$definition->isExpected
                    && !($kept[$inner] ?? true)
                    && ($startPrediction[$definition->ruleId] ?? null) !== null
                ) {
                    $elements[] = -$inner - 1;
                    $changed = true;
                    ++$inlined;

                    continue;
                }

                $elements[] = $inner;
            }

            // A sequence written exactly as it is declared is read off the
            // rule itself, and saying nothing about it costs nothing
            if ($changed) {
                $result[$id] = $elements;
            }
        }

        $context->sequencePrediction = $result;

        $context->logger->info('{elements} element(s) of {rules} sequence(s) are read in place', [
            'elements' => $inlined,
            'rules' => \count($result),
        ]);
    }
}
