<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition\Reducer;

use Phplrt\Parser\Builder\Definition\SourceReference;

/**
 * Converts the rule into the node of the syntax tree using the given PHP code.
 *
 * The code is the body of the callback: the signature, the variables it refers
 * to and the value it returns are the business of the generator.
 *
 * For example, the `Literal -> { return $children; }` rule of a grammar file is
 * read as `new PhpCodeReducer('return $children;')`.
 */
final class PhpCodeReducer implements ReducerInterface
{
    public private(set) ?SourceReference $source = null;

    /**
     * @param non-empty-string $code
     */
    public function __construct(
        /**
         * @var non-empty-string
         */
        public readonly string $code,
    ) {}

    /**
     * @param non-empty-string $pathname
     * @param int<0, max> $offset
     * @return $this
     */
    public function setSource(string $pathname, int $offset): self
    {
        $this->source = new SourceReference($pathname, $offset);

        return $this;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
