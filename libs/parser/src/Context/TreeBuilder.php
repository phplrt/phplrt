<?php

declare(strict_types=1);

namespace Phplrt\Parser\Context;

use Phplrt\Parser\BuilderInterface;
use Phplrt\Parser\Context;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
class TreeBuilder implements BuilderInterface
{
    /**
     * @var array<array-key, callable(Context, mixed):mixed>
     */
    private array $reducers;

    /**
     * @param iterable<array-key, callable(Context, mixed):mixed> $reducers
     */
    public function __construct(iterable $reducers)
    {
        if ($reducers instanceof \Traversable) {
            $reducers = \iterator_to_array($reducers);
        }

        $this->reducers = $reducers;
    }

    public function build(Context $context, $result)
    {
        if (isset($this->reducers[$context->state])) {
            return ($this->reducers[$context->state])($context, $result);
        }

        return $result;
    }
}
