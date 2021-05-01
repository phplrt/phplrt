<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Renderer;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Driver\DriverInterface;

final class Renderer implements RendererInterface
{
    /**
     * @var positive-int
     */
    private const DEFAULT_LENGTH = 30;

    /**
     * @var string
     */
    private const DEFAULT_WRAP = '"';

    /**
     * @var array { 0: array<string>, 1: array<string> }
     * @see https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.double
     */
    private const DEFAULT_REPLACEMENTS = [
        ["\n", "\r", "\t", "\v", "\e", "\f"],
        ['\n', '\r', '\t', '\v', '\e', '\f'],
    ];

    /**
     * @var string
     */
    private const DEFAULT_OVERFLOW_SUFFIX = ' (%s+)';

    /**
     * @var positive-int
     */
    private int $length;

    /**
     * @var string
     */
    private string $wrap = self::DEFAULT_WRAP;

    /**
     * @var array { 0: array<string>, 1: array<string> }
     */
    private array $replacements = self::DEFAULT_REPLACEMENTS;

    /**
     * @var string
     */
    private string $suffix = self::DEFAULT_OVERFLOW_SUFFIX;

    /**
     * @param positive-int $length
     */
    public function __construct(int $length = self::DEFAULT_LENGTH)
    {
        $this->length = \max(1, $length);
    }

    /**
     * @param positive-int $length
     * @return $this
     */
    public function withLength(int $length): self
    {
        $self = clone $this;
        $self->length = \max(1, $length);

        return $self;
    }

    /**
     * @param string $char
     * @return $this
     */
    public function withWrapCharacter(string $char): self
    {
        $self = clone $this;
        $self->wrap = $char;

        return $self;
    }

    /**
     * @param string $suffix
     * @return $this
     */
    public function withSuffix(string $suffix): self
    {
        $self = clone $this;
        $self->suffix = $suffix;

        return $self;
    }

    /**
     * @param TokenInterface $token
     * @return string
     */
    public function render(TokenInterface $token): string
    {
        switch (true) {
            case $token->getName() === DriverInterface::UNKNOWN_TOKEN_NAME:
            case $token->getName() === $token->getValue():
                return $this->value($token);
        }

        return \sprintf('%s (%s)', $this->value($token), $this->name($token));
    }

    /**
     * @param TokenInterface $token
     * @return string
     */
    public function value(TokenInterface $token): string
    {
        $value = $this->inline($token->getValue());

        if ($this->shouldBeShorten($value)) {
            return $this->shorten($value);
        }

        return $this->wrap($value);
    }

    /**
     * @param string $value
     * @return string
     */
    private function inline(string $value): string
    {
        return \preg_replace('/\h+/u', ' ', $value) ?? $value;
    }

    /**
     * @param string $value
     * @return bool
     */
    private function shouldBeShorten(string $value): bool
    {
        $length = \mb_strlen($value);

        return $length > $this->length + \mb_strlen($this->suffix($value));
    }

    /**
     * @param string $value
     * @return string
     */
    private function suffix(string $value): string
    {
        return \sprintf($this->suffix, \mb_strlen($value) - $this->length);
    }

    /**
     * @param string $value
     * @return string
     */
    private function shorten(string $value): string
    {
        $prefix = $this->wrap(\mb_substr($value, 0, $this->length) . '…');

        return $prefix . $this->suffix($value);
    }

    /**
     * @param string $value
     * @return string
     */
    private function wrap(string $value): string
    {
        return $this->wrap . $this->escape($value) . $this->wrap;
    }

    /**
     * @param string $value
     * @return string
     */
    private function escape(string $value): string
    {
        /**
         * @psalm-var array<string> $from
         * @psalm-var array<string> $to
         */
        [$from, $to] = $this->replacements;

        $result = '';

        foreach (\mb_str_split(\str_replace($from, $to, $value)) as $char) {
            switch (true) {
                case $char === $this->wrap:
                    $result .= "\\$char";
                    break;

                case ! $this->isPrintable($char):
                    $result .= $this->toEscapedSequence($char);
                    break;

                default:
                    $result .= $char;
            }
        }

        return $result;
    }

    /**
     * @param string $char
     * @return string
     */
    private function toEscapedSequence(string $char): string
    {
        $result = '';

        foreach (\mb_str_split($char) as $byte) {
            $hex = \dechex(\mb_ord($byte));

            $result .= '\u{' . \str_pad($hex, 4, '0', \STR_PAD_LEFT) . '}';
        }

        return $result;
    }

    /**
     * @param string $char
     * @return bool
     */
    private function isPrintable(string $char): bool
    {
        if (\function_exists('\\ctype_print')) {
            return \ctype_print($char);
        }

        return true;
    }

    /**
     * @param TokenInterface $token
     * @return string
     */
    public function name(TokenInterface $token): string
    {
        return $token->getName();
    }
}
