<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition;

/**
 * A rule that recognizes a sequence of values instead of a single one.
 *
 * The values of a rule it refers to are joined with the values of its
 * neighbours, unless that rule is present in the resulting tree on its own.
 */
interface SequenceRuleDefinitionInterface {}
