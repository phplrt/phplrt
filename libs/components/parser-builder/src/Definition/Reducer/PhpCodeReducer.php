<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition\Reducer;

use Phplrt\Contracts\Source\ReadableInterface;
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
    /**
     * The place of the source code this reducer has been written in, in case
     * it has been written at all rather than built by hand.
     */
    public private(set) ?SourceReference $context = null;

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
     * @param int<0, max> $offset
     * @param int<0, max> $length
     * @return $this
     */
    public function setSource(ReadableInterface $source, int $offset, int $length = 0): self
    {
        $this->context = new SourceReference($source, $offset, $length);

        return $this;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
