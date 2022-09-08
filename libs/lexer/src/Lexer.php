<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @var array<string>
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
     * @var bool
     */
    private bool $throwOnError = true;

    /**
     * @param array<string> $tokens
     * @param array<string> $skip
     * @param DriverInterface|null $driver
     */
    public function __construct(array $tokens = [], array $skip = [], DriverInterface $driver = null)
    {
        $this->driver = $driver ?? new Markers();
        $this->tokens = $tokens;
        $this->skip = $skip;
    }

    /**
     * @return void
     */
    public function disableUnrecognizedTokenException()
    {
        $this->throwOnError = false;
    }

    /**
     * @return DriverInterface
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * @param DriverInterface $driver
     * @return $this
     */
    public function setDriver(DriverInterface $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function skip(string ...$tokens): self
    {
        $this->skip = \array_merge($this->skip, $tokens);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function append(string $token, string $pattern): self
    {
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
    public function appendMany(array $tokens): self
    {
        $this->reset();
        $this->tokens = \array_merge($this->tokens, $tokens);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(string $token, string $pattern): self
    {
        $this->reset();
        $this->tokens = \array_merge([$token => $pattern], $this->tokens);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function prependMany(array $tokens, bool $reverseOrder = false): self
    {
        $this->reset();
        $this->tokens = \array_merge($reverseOrder ? \array_reverse($tokens) : $tokens, $this->tokens);

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

            $this->skip = \array_filter($this->skip, fn(string $haystack): bool => $haystack !== $token);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function lex($source, int $offset = 0): iterable
    {
        return $this->run(File::new($source), $offset);
    }

    /**
     * @param ReadableInterface $source
     * @param int $offset
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

            if (\count($unknown)) {
                if ($this->throwOnError) {
                    throw UnrecognizedTokenException::fromToken($source, $this->reduceUnknownToken($unknown));
                }
                yield $this->reduceUnknownToken($unknown);
                $unknown = [];
            }

            yield $token;
        }

        if (\count($unknown)) {
            if ($this->throwOnError) {
                throw UnrecognizedTokenException::fromToken($source, $this->reduceUnknownToken($unknown));
            }
            yield $this->reduceUnknownToken($unknown);
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

        $value = (string)\array_reduce($tokens, $concat, '');

        return new Token(\reset($tokens)->getName(), $value, \reset($tokens)->getOffset());
    }
}
