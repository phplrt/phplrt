<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Source\File;
use Phplrt\Lexer\Token\Token;
use Phplrt\Lexer\Driver\Markers;
use Phplrt\Lexer\Token\EndOfInput;
use Phplrt\Lexer\Driver\DriverInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Exception\UnrecognizedTokenException;

class Lexer implements LexerInterface, MutableLexerInterface
{
    /**
     * @var array<non-empty-string, non-empty-string>
     */
    protected array $tokens = [];

    /**
     * @var array<non-empty-string>
     */
    protected array $skip = [];

    private DriverInterface $driver;

    private bool $throwOnError = true;

    /**
     * @param array<non-empty-string, non-empty-string> $tokens
     * @param array<non-empty-string> $skip
     * @param DriverInterface|null $driver
     */
    public function __construct(array $tokens = [], array $skip = [], DriverInterface $driver = null)
    {
        $this->driver = $driver ?? new Markers();
        $this->tokens = $tokens;
        $this->skip = $skip;
    }

    public function disableUnrecognizedTokenException(): void
    {
        $this->throwOnError = false;
    }

    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    public function setDriver(DriverInterface $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    public function skip(string ...$tokens): self
    {
        $this->skip = \array_merge($this->skip, $tokens);

        return $this;
    }

    public function append(string $token, string $pattern): self
    {
        $this->reset();
        $this->tokens[$token] = $pattern;

        return $this;
    }

    private function reset(): void
    {
        $this->driver->reset();
    }

    public function appendMany(array $tokens): self
    {
        $this->reset();
        $this->tokens = \array_merge($this->tokens, $tokens);

        return $this;
    }

    public function prepend(string $token, string $pattern): self
    {
        $this->reset();
        $this->tokens = \array_merge([$token => $pattern], $this->tokens);

        return $this;
    }

    public function prependMany(array $tokens, bool $reverseOrder = false): self
    {
        $this->reset();
        $this->tokens = \array_merge($reverseOrder ? \array_reverse($tokens) : $tokens, $this->tokens);

        return $this;
    }

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
     *
     * @param string|resource|ReadableInterface $source
     * @param int<0, max> $offset
     * @return iterable<TokenInterface>
     */
    public function lex($source, int $offset = 0): iterable
    {
        return $this->run(File::new($source), $offset);
    }

    /**
     * @param int<0, max> $offset
     *
     * @return iterable<TokenInterface>
     */
    private function run(ReadableInterface $source, int $offset): iterable
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

            if ($unknown !== []) {
                if ($this->throwOnError) {
                    throw UnrecognizedTokenException::fromToken($source, $this->reduceUnknownToken($unknown));
                }

                yield $this->reduceUnknownToken($unknown);

                $unknown = [];
            }

            yield $token;
        }

        if ($unknown !== []) {
            if ($this->throwOnError) {
                throw UnrecognizedTokenException::fromToken($source, $this->reduceUnknownToken($unknown));
            }

            yield $this->reduceUnknownToken($unknown);
        }

        if (!\in_array(TokenInterface::END_OF_INPUT, $this->skip, true)) {
            /** @psalm-suppress all : Psalm error: (offset) int<0, max> + (size) int<0, max> = int<0, max> */
            yield new EndOfInput(isset($token) ? $token->getOffset() + $token->getBytes() : 0);
        }
    }

    /**
     * @param array<TokenInterface> $tokens
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
