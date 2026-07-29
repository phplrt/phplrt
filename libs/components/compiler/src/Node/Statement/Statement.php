<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

use Phplrt\Compiler\Node\Node;

/**
 * A part of what a rule of the parser recognizes.
 *
 * @phpstan-sealed Alternation|Concatenation|InlinePattern|InlineValue|Predicate|Repetition|RuleReference|TokenReference
 */
abstract readonly class Statement extends Node {}
