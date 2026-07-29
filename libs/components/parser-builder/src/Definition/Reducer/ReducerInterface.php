<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition\Reducer;

/**
 * Converts the rule into the node of the syntax tree.
 *
 * A reducer written as a callback can be executed, but cannot be dumped into
 * the generated parser, while a reducer written as code can be dumped, but
 * cannot be executed until the parser is generated. Which of them is required
 * depends on what the grammar is compiled for.
 *
 * @phpstan-sealed CallableReducer|PhpCodeReducer
 */
interface ReducerInterface extends \Stringable {}
