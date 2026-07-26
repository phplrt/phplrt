<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Definition\Reducer;

/**
 * Converts the rule into the node of the syntax tree using the given PHP code.
 *
 * The code is the body of the callback: the signature, the variables it refers
 * to and the value it returns are the business of the generator.
 *
 * For example, the `Literal -> { return $children; }` rule of a grammar file is
 * read as `new PhpCodeReducer('return $children;')`.
 */
final readonly class PhpCodeReducer implements ReducerInterface
{
    /**
     * @param non-empty-string $code
     */
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $code,
    ) {}

    public function __toString(): string
    {
        return $this->code;
    }
}
