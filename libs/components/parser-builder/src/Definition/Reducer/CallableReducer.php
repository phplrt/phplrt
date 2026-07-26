<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition\Reducer;

use Phplrt\Parser\Context;

/**
 * Converts the rule into the node of the syntax tree using the given callback.
 *
 * @phpstan-type CallbackType callable(Context, mixed): mixed
 */
final readonly class CallableReducer implements ReducerInterface
{
    /**
     * @var CallbackType&\Closure
     */
    public \Closure $callback;

    /**
     * @param CallbackType $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback(...);
    }

    /**
     * @param CallbackType $callback
     */
    public static function createFromCallable(callable $callback): self
    {
        return new self($callback);
    }

    public function __toString(): string
    {
        return 'callback';
    }
}
