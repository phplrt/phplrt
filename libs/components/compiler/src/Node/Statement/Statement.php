<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

use Phplrt\Compiler\Node\Node;

/**
 * A part of what a rule of the parser recognizes.
 *
 * @phpstan-sealed Adjacency|Alternation|Annotated|Concatenation|InlinePattern|InlineValue|Predicate|Repetition|RuleReference|TokenReference
 *
 * @readonly
 */
abstract class Statement extends Node {}
