<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer;

use JetBrains\PhpStorm\Language;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Driver\DriverInterface;
use Phplrt\Lexer\Driver\Markers;
use Phplrt\Lexer\Exception\UnrecognizedTokenException;
use Phplrt\Lexer\Token\EndOfInput;
use Phplrt\Lexer\Token\Token;
use Phplrt\Source\File;

class Lexer implements LexerInterface, MutableLexerInterface
{
    /**
     * @var array<string, string>
     */
    protected array $tokens;

    /**
     * @var array<string>
     */
    protected array $skip;

    /**
     * @var DriverInterface
     */
    private DriverInterface $driver;

    /**
     * @param array<string, string> $tokens
     * @param array<string> $skip
     */
    public function __construct(array $tokens = [], array $skip = [])
    {
        $this->driver = new Markers();
        $this->tokens = $tokens;
        $this->skip = $skip;
    }

    /**
     * {@inheritDoc}
     */
    public function skip(string ...$names): self
    {
        $this->skip = \array_merge($this->skip, $names);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function append(
        string $token,
        #[Language("RegExp")]
        string $pattern
    ): self {
        $this->reset();
        $this->tokens[$token] = $pattern;

        return $this;
    }

    /**
     * @return void
     */
    private function reset(): void
    {
        $this->driver->reset();
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(
        string $token,
        #[Language("RegExp")]
        string $pattern
    ): self {
        $this->reset();
        $this->tokens = \array_merge([$token => $pattern], $this->tokens);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string ...$tokens): self
    {
        $this->reset();

        foreach ($tokens as $token) {
            unset($this->tokens[$token]);

            $this->skip = \array_filter($this->skip, static fn(string $haystack): bool => $haystack !== $token);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     * @param positive-int|0 $offset
     */
    public function lex($source, int $offset = 0): iterable
    {
        return $this->run(File::new($source), $offset);
    }

    /**
     * @param ReadableInterface $source
     * @param positive-int|0 $offset
     * @return \Generator
     */
    private function run(ReadableInterface $source, int $offset): \Generator
    {
        $unknown = [];

        foreach ($this->driver->run($this->tokens, File::new($source), $offset) as $token) {
            if (\in_array($token->getName(), $this->skip, true)) {
                continue;
            }

            if ($token->getName() === $this->driver::UNKNOWN_TOKEN_NAME) {
                $unknown[] = $token;
                continue;
            }

            if (\count($unknown) && $token->getName() !== $this->driver::UNKNOWN_TOKEN_NAME) {
                throw UnrecognizedTokenException::fromToken($source, $this->reduceUnknownToken($unknown));
            }

            yield $token;
        }

        if ($unknown !== []) {
            throw UnrecognizedTokenException::fromToken($source, $this->reduceUnknownToken($unknown));
        }

        if (! \in_array(EndOfInput::END_OF_INPUT, $this->skip, true)) {
            yield new EndOfInput(isset($token) ? $token->getOffset() + $token->getBytes() : 0);
        }
    }

    /**
     * @param array|TokenInterface[] $tokens
     * @return TokenInterface
     */
    private function reduceUnknownToken(array $tokens): TokenInterface
    {
        $concat = static function (string $data, TokenInterface $token): string {
            return $data . $token->getValue();
        };

        $value = \array_reduce($tokens, $concat, '');

        return new Token(\reset($tokens)->getName(), $value, \reset($tokens)->getOffset());
    }
}
