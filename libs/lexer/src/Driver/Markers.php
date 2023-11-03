<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Driver;

use Phplrt\Lexer\Token\Token;
use Phplrt\Lexer\Token\Composite;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Compiler\Markers as MarkersCompiler;

class Markers extends Driver
{
    /**
     * @var string
     */
    private const UNKNOWN_PATTERN = '.+?';

    /**
     * @param MarkersCompiler|null $compiler
     */
    public function __construct(MarkersCompiler $compiler = null)
    {
        parent::__construct($compiler ?? new MarkersCompiler());
    }

    /**
     * @param array<non-empty-string, non-empty-string> $tokens
     * @param int<0, max> $offset
     * @return iterable<TokenInterface>
     */
    public function run(array $tokens, ReadableInterface $source, int $offset = 0): iterable
    {
        $pattern = $this->getPattern(\array_merge($tokens, [
            self::UNKNOWN_TOKEN_NAME => self::UNKNOWN_PATTERN,
        ]));

        $result = $this->match($pattern, $source->getContents(), $offset);

        foreach ($result as $payload) {
            /** @var non-empty-string $name */
            $name = \array_pop($payload);

            /** @psalm-suppress InvalidArgument */
            yield $this->make($name, $payload);
        }
    }

    /**
     * @param non-empty-string $pattern
     * @param int<0, max> $offset
     * @return array<array<int<0, max>, array{string, int}>|array{MARK: non-empty-string}>
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    private function match(string $pattern, string $source, int $offset): array
    {
        \preg_match_all($pattern, $source, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE, $offset);

        return $matches;
    }

    /**
     * @param non-empty-string $name
     * @param array<array{string, int<0, max>}> $payload
     */
    private function make(string $name, array $payload): TokenInterface
    {
        if (\count($payload) > 1) {
            return Composite::fromArray($this->transform($name, $payload));
        }

        return new Token($name, ...$payload[0]);
    }

    /**
     * @param non-empty-string $name
     * @param non-empty-array<array-key, array{string, int<0, max>}> $payload
     * @return non-empty-array<int, TokenInterface>
     */
    private function transform(string $name, array $payload): array
    {
        $result = [];

        foreach ($payload as $index => $value) {
            $result[] = new Token(\is_int($index) ? $name : $index, ...$value);
        }

        return $result;
    }
}
