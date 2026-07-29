<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;
use Phplrt\Parser\Builder\Definition\Reducer\ReducerInterface;

/**
 * A rule is converted into a node by something that cannot be written down.
 */
final class UnsupportedReducerException extends GeneratorException
{
    public static function becauseReducerCannotBeGenerated(
        int $rule,
        ReducerInterface $reducer,
        ?\Throwable $previous = null,
    ): self {
        $message = \vsprintf('The rule #%d is reduced by %s, while only a %s can be generated', [
            $rule,
            $reducer::class,
            PhpCodeReducer::class,
        ]);

        return new self($message, previous: $previous);
    }
}
