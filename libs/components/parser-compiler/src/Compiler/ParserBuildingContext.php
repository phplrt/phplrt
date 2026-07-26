<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Compiler;

use Phplrt\Compiler\Parser\Definition\RuleDefinition;

/**
 * Contains the grammar the compiler passes rewrite and check.
 *
 * The rule definitions are copies of the ones the builder has been given, so a
 * pass is free to rewrite them.
 */
final class ParserBuildingContext
{
    public function __construct(
        /**
         * Contains the rule the analysis starts at, or {@see null} in case of
         * the grammar contains no rules
         */
        public ?RuleDefinition $initial = null,
        /**
         * The rules of the grammar.
         *
         * A pass rewriting the grammar is the one keeping this list in sync
         * with the rules the initial one refers to.
         *
         * @var list<RuleDefinition>
         */
        public array $rules = [],
    ) {}
}
