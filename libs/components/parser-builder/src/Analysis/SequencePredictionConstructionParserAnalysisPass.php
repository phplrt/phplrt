<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Analysis;

use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Optional;

/**
 * Describes which elements of a sequence may be left out.
 *
 * An optional element is entered on every reading of the sequence only to be
 * given up at once most of the time, and a rule that is given up before it
 * reads anything leaves nothing behind. Such an element is written down as
 * the rule it wraps, negated, so the sequence reads the rule itself and goes
 * on either way, without entering the optional at all.
 *
 * An optional that becomes a node of the tree is left as it is: what it
 * leaves behind is exactly the node.
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

                if ($definition instanceof Optional && !($kept[$inner] ?? true)) {
                    $elements[] = -$definition->ruleId - 1;
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

        $context->logger->info('{elements} optional element(s) of {rules} sequence(s) are read in place', [
            'elements' => $inlined,
            'rules' => \count($result),
        ]);
    }
}
