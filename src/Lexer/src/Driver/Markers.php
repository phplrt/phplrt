<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Driver;

use Phplrt\Source\File;
use Phplrt\Lexer\Token\Token;
use Phplrt\Lexer\Token\Composite;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotAccessibleException;

/**
 * Class Markers
 */
class Markers extends Driver
{
    /**
     * @var string
     */
    private const UNKNOWN_PATTERN = '.+?';

    /**
     * @var string|null
     */
    private $pattern;

    /**
     * @var MarkersCompiler|CompilerInterface
     */
    private $compiler;

    /**
     * Markers constructor.
     *
     * @param array|string[] $tokens
     */
    public function __construct(array $tokens)
    {
        parent::__construct($tokens);

        $this->compiler = new MarkersCompiler();
    }

    /**
     * @return CompilerInterface
     */
    public function getCompiler(): CompilerInterface
    {
        return $this->compiler;
    }

    /**
     * @param ReadableInterface $source
     * @param int $offset
     * @return iterable|TokenInterface[]|string
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public function lex($source, int $offset = 0): iterable
    {
        $tokens = $this->match($this->getPattern(), File::new($source)->getContents(), $offset);

        foreach ($tokens as $index => $payload) {
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
     * @return string
     */
    public function getPattern(): string
    {
        if ($this->pattern === null) {
            $this->pattern = $this->compile($this->getTokens());
        }

        return $this->pattern;
    }

    /**
     * @param array $tokens
     * @return string
     */
    public function compile(array $tokens): string
    {
        return $this->compiler->compile($tokens);
    }

    /**
     * @return array|string[]
     */
    private function getTokens(): array
    {
        return \array_merge($this->tokens, [
            self::UNKNOWN_TOKEN_NAME => self::UNKNOWN_PATTERN,
        ]);
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

        return new Token($this->name($name), ...$payload[0]);
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
            $result[] = new Token(\is_int($index) ? $this->name($name) : $index, ...$value);
        }

        return $result;
    }
}
