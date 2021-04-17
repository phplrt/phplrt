<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Driver;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Compiler\Markers as MarkersCompiler;
use Phplrt\Lexer\Token\Composite;
use Phplrt\Lexer\Token\Token;

class Markers extends Driver
{
    /**
     * @var string
     */
    private const UNKNOWN_PATTERN = '.+?';

    /**
     * Markers constructor.
     *
     * @param MarkersCompiler|null $compiler
     */
    public function __construct(MarkersCompiler $compiler = null)
    {
        parent::__construct($compiler ?? new MarkersCompiler());
    }

    /**
     * @param array $tokens
     * @param ReadableInterface $source
     * @param int $offset
     * @return iterable|TokenInterface[]
     */
    public function run(array $tokens, ReadableInterface $source, int $offset = 0): iterable
    {
        $pattern = $this->getPattern(\array_merge($tokens, [
            self::UNKNOWN_TOKEN_NAME => self::UNKNOWN_PATTERN,
        ]));

        $result = $this->match($pattern, $source->getContents(), $offset);

        foreach ($result as $payload) {
            $name = \array_pop($payload);

            yield $this->make($name, $payload);
        }
    }

    /**
     * @param string $pattern
     * @param string $source
     * @param int $offset
     * @return array
     */
    private function match(string $pattern, string $source, int $offset): array
    {
        \preg_match_all($pattern, $source, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE, $offset);

        return $matches;
    }

    /**
     * @param string $name
     * @param array $payload
     * @return TokenInterface
     */
    private function make(string $name, array $payload): TokenInterface
    {
        if (\count($payload) > 1) {
            return Composite::fromArray($this->transform($name, $payload));
        }

        return new Token($name, ...$payload[0]);
    }

    /**
     * @param string $name
     * @param array $payload
     * @return array|TokenInterface[]
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
