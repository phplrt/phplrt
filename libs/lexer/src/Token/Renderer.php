<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\TokenInterface;

final class Renderer
{
    /**
     * @var int
     */
    private const DEFAULT_LENGTH = 30;

    /**
     * @var string
     */
    private const DEFAULT_WRAP = '"';

    /**
     * @var array
     */
    private const DEFAULT_REPLACEMENTS = [
        ["\0", "\n", "\t"],
        ['\0', '\n', '\t'],
    ];

    /**
     * @var string
     */
    private const DEFAULT_OVERFLOW_SUFFIX = ' (%s+)';

    private int $length = self::DEFAULT_LENGTH;

    private string $wrap = self::DEFAULT_WRAP;

    /**
     * @var array{array<non-empty-string>, array<non-empty-string>}
     */
    private array $replacements = self::DEFAULT_REPLACEMENTS;

    private string $suffix = self::DEFAULT_OVERFLOW_SUFFIX;

    public function render(TokenInterface $token): string
    {
        switch (true) {
            case $token instanceof EndOfInput:
                return 'end of input';
            case $token instanceof UnknownToken:
            case $token->getName() === $token->getValue():
                return $this->value($token);
        }

        return \sprintf('%s (%s)', $this->value($token), $this->name($token));
    }

    public function value(TokenInterface $token): string
    {
        $value = $this->escape($this->inline($token->getValue()));

        if ($this->shouldBeShorten($value)) {
            return $this->shorten($value);
        }

        return $this->wrap . $this->replace($value) . $this->wrap;
    }

    private function escape(string $value): string
    {
        return \addcslashes($value, $this->wrap);
    }

    private function inline(string $value): string
    {
        return (string) (\preg_replace('/\h+/u', ' ', $value) ?? $value);
    }

    private function shouldBeShorten(string $value): bool
    {
        $length = \mb_strlen($value);

        return $length > $this->length + \mb_strlen($this->suffix($value));
    }

    private function suffix(string $value): string
    {
        return \sprintf($this->suffix, \mb_strlen($value) - $this->length);
    }

    private function shorten(string $value): string
    {
        $prefix = $this->wrap(\mb_substr($value, 0, $this->length) . '…');

        return $prefix . $this->suffix($value);
    }

    private function wrap(string $value): string
    {
        return $this->wrap . $this->replace($value) . $this->wrap;
    }

    private function replace(string $value): string
    {
        [$from, $to] = $this->replacements;

        return \str_replace($from, $to, $value);
    }

    public function name(TokenInterface $token): string
    {
        return $token->getName();
    }
}
