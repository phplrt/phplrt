<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Reducer;

use Phplrt\Compiler\Node\Node;

/**
 * Converts a rule of the parser into a node of the syntax tree.
 *
 * @phpstan-sealed ClassReducer|CodeReducer
 */
abstract readonly class Reducer extends Node {}
