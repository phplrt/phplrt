<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Runtime;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Compiler\Runtime
 */
final class PrintableNode implements NodeInterface, \Stringable
{
    /**
     * @param int<0, max> $offset
     * @param non-empty-string $state
     * @param array<array-key, PrintableNode|TokenInterface> $children
     */
    public function __construct(
        private readonly int $offset,
        private readonly string $state,
        public readonly array $children,
    ) {}

    /**
     * @return \Traversable<non-empty-string, array<array-key, PrintableNode>>
     */
    public function getIterator(): \Traversable
    {
        yield 'children' => $this->children;
    }

    public function __toString(): string
    {
        return \implode("\n", $this->render(0));
    }

    /**
     * @param int<0, max> $depth
     *
     * @return array<string>
     */
    public function render(int $depth): array
    {
        $prefix = \str_repeat('    ', $depth);

        $result = [
            $prefix . '<' . $this->state . ' offset="' . $this->offset . '">',
        ];

        foreach ($this->children as $child) {
            switch (true) {
                case $child instanceof self:
                    /** @psalm-suppress RedundantFunctionCall: PHP 7.4 unpacking expect only integer keys */
                    $result = [
                        ...\array_values($result),
                        ...\array_values($child->render($depth + 1)),
                    ];
                    break;

                case $child instanceof TokenInterface:
                    $result[] = $prefix . '    <' . $child->getName() . ' offset="' . $child->getOffset() . '">' .
                        $child->getValue() . '</' . $child->getName() . '>';
                    break;

                default:
                    $result[] = $prefix . '    <' . $child . ' />';
            }
        }

        $result[] = $prefix . '</' . $this->state . '>';

        return $result;
    }

    public function getState(): string
    {
        return $this->state;
    }
}
