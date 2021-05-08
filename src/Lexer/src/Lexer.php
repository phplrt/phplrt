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
use Phplrt\Lexer\Exception\UnrecognizedTokenException;
use Phplrt\Lexer\Internal\Regex\MarkersCompiler;
use Phplrt\Lexer\Token\Composite;
use Phplrt\Lexer\Token\EndOfInput;
use Phplrt\Lexer\Token\Token;
use Phplrt\Source\File;

class Lexer implements MutableLexerInterface
{
    /**
     * @var string
     */
    public const T_UNKNOWN_NAME = 'T_UNKNOWN';

    /**
     * @var string
     */
    private const T_UNKNOWN_PATTERN = '.+?';

    /**
     * @var array<string, string>
     */
    protected array $tokens;

    /**
     * @var array<string>
     */
    protected array $skip;

    /**
     * @var string|null
     */
    private ?string $pattern = null;

    /**
     * @param array<string, string> $tokens
     * @param array<string> $skip
     */
    public function __construct(array $tokens = [], array $skip = [])
    {
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
     * @return void
     */
    private function reset(): void
    {
        $this->pattern = null;
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
     */
    public function lex($source, int $offset = 0): iterable
    {
        $source = File::new($source);
        $result = $this->match($source->getContents(), $offset);
        $error = null;

        /** @var array<string> $payload */
        foreach ($result as $payload) {
            $name = \array_pop($payload);

            // Capture offset
            $previous = $offset;
            $offset += \strlen($payload[0]);

            // Skip non-captured tokens
            if (\in_array($name, $this->skip, true)) {
                continue;
            }

            // Capture error token
            if ($name === self::T_UNKNOWN_NAME) {
                $error ??= new Token(self::T_UNKNOWN_NAME, '', $previous);
                $error->value .= $payload[0];
                continue;
            }

            // Transition to a known token from error token
            if ($error !== null) {
                throw UnrecognizedTokenException::fromToken($source, $error);
            }

            yield \count($payload) > 1
                ? Composite::fromArray($name, $payload, $previous)
                : new Token($name, $payload[0], $previous)
            ;
        }

        if ($error !== null) {
            throw UnrecognizedTokenException::fromToken($source, $error);
        }

        if (! \in_array(EndOfInput::END_OF_INPUT, $this->skip, true)) {
            yield new EndOfInput($offset);
        }
    }

    /**
     * @return string
     */
    private function compile(): string
    {
        $compiler = new MarkersCompiler();

        return $compiler->compile(\array_merge($this->tokens, [
            self::T_UNKNOWN_NAME => self::T_UNKNOWN_PATTERN,
        ]));
    }

    /**
     * @param string $source
     * @param int $offset
     * @return array
     */
    private function match(string $source, int $offset): array
    {
        $this->pattern ??= $this->compile();

        \preg_match_all($this->pattern, $source, $matches, \PREG_SET_ORDER, $offset);

        return $matches;
    }
}
