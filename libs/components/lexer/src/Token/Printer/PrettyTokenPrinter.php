<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token\Printer;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\TokenInterface;

final readonly class PrettyTokenPrinter implements TokenPrinterInterface
{
    private const int DEFAULT_LENGTH = 30;

    private const string DEFAULT_WRAP = '"';

    private const array DEFAULT_REPLACEMENTS = [
        ["\0", "\n", "\t", "\v"],
        ['\0', '\n', '\t', '\v'],
    ];

    private const string DEFAULT_OVERFLOW_SUFFIX = ' (%s+)';

    public function __construct(
        /**
         * @var int<1, max>
         */
        private int $length = self::DEFAULT_LENGTH,
        /**
         * @var non-empty-string
         */
        private string $wrap = self::DEFAULT_WRAP,
        /**
         * @var array{array<non-empty-string>, array<non-empty-string>}
         */
        private array $replacements = self::DEFAULT_REPLACEMENTS,
        /**
         * @var non-empty-string
         */
        private string $suffix = self::DEFAULT_OVERFLOW_SUFFIX,
    ) {}

    public function print(TokenInterface $token): string
    {
        $name = $token->name;
        $channel = $token->channel;

        if ($channel === Channel::EndOfInput) {
            return 'end of input';
        }

        if ($channel === Channel::Unknown && $name === null) {
            return \sprintf('%s (unknown token)', $this->printValue($token));
        }

        if ($channel instanceof Channel) {
            if ($name === null) {
                return $this->printValue($token);
            }

            return \sprintf('%s (%s)', $this->printValue($token), $name);
        }

        if ($name === null) {
            return \sprintf('%s (of %s)', $this->printValue($token), $channel->name);
        }

        return \sprintf('%s (%s of %s)', $this->printValue($token), $name, $channel->name);
    }

    public function printValue(TokenInterface $token): string
    {
        $value = $this->escape($this->inline($token->value));

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
        return \preg_replace('/\h+/u', ' ', $value) ?? $value;
    }

    private function strlen(string $value): int
    {
        if (\function_exists('\\grapheme_strlen')) {
            $result = \grapheme_strlen($value);

            if (\is_int($result)) {
                return $result;
            }
        }

        if (\function_exists('\\mb_strlen')) {
            return \mb_strlen($value);
        }

        return \strlen($value);
    }

    /**
     * @param int<0, max> $offset
     * @param int<0, max>|null $length
     */
    private function substr(string $value, int $offset, ?int $length = null): string
    {
        if (\function_exists('\\grapheme_substr')) {
            $result = \grapheme_substr($value, $offset, $length);

            if ($result !== false) {
                return $result;
            }
        }

        if (\function_exists('\\mb_substr')) {
            return \mb_substr($value, $offset, $length);
        }

        return \substr($value, $offset, $length);
    }

    private function shouldBeShorten(string $value): bool
    {
        $length = $this->strlen($value);

        return $length > $this->length + $this->strlen($this->suffix($value));
    }

    private function suffix(string $value): string
    {
        return \sprintf($this->suffix, $this->strlen($value) - $this->length);
    }

    private function shorten(string $value): string
    {
        $prefix = $this->wrap($this->substr($value, 0, $this->length) . '…');

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
}
