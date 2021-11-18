<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Printer;

use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Token\Channel;

final class Printer implements PrinterInterface
{
    /**
     * @param PrinterCreateInfo $info
     */
    public function __construct(
        public readonly PrinterCreateInfo $info = new PrinterCreateInfo()
    ) {
    }

    /**
     * @param TokenInterface $token
     * @return string
     */
    public function print(TokenInterface $token): string
    {
        $name = $this->name($token);
        $value = $this->value($token);

        if ($this->shouldBeTruncated($value)) {
            $chunk = \mb_substr($value, 0, $this->info->length);
            $delta = \mb_strlen($value) - \mb_strlen($chunk);

            return \sprintf('"%s…" (%d+)%s', $this->escape($chunk), $delta, $name);
        }

        return \sprintf('"%s"%s', $this->escape($value), $name);
    }

    /**
     * @param TokenInterface $token
     * @return string
     */
    private function value(TokenInterface $token): string
    {
        return $token->getValue();
    }

    /**
     * @param string $value
     * @return bool
     */
    private function shouldBeTruncated(string $value): bool
    {
        $length = \mb_strlen($value);

        return $length > $this->info->length + 1;
    }

    /**
     * @param string $value
     * @return string
     */
    private function escape(string $value): string
    {
        $value = \str_replace(
            \array_keys($this->info->replace),
            \array_values($this->info->replace),
            $value
        );

        $result = '';

        /** @var string $char */
        foreach (\mb_str_split($value) as $char) {
            $result .= match (true) {
                $char === '"' => '\\"',
                !$this->isPrintable($char) => $this->toEscapedSequence($char),
                default => $char,
            };
        }

        return $result;
    }

    /**
     * @param string $char
     * @return bool
     * @codeCoverageIgnore
     */
    private function isPrintable(string $char): bool
    {
        if (\function_exists('\\ctype_print')) {
            return \ctype_print($char);
        }

        /**
         * Ctype extension "print" polyfill.
         * @link https://github.com/symfony/polyfill-ctype/blob/v1.23.0/Ctype.php#L128-L133
         */
        return $char !== '' && !\preg_match('/[^ -~]/', $char);
    }

    /**
     * @param string $char
     * @return string
     */
    private function toEscapedSequence(string $char): string
    {
        $result = '';

        /** @var string $byte */
        foreach (\mb_str_split($char) as $byte) {
            $hex = \strtoupper(\dechex(\mb_ord($byte)));
            $result .= \strlen($hex) > 2
                ? '\u{' . \str_pad($hex, 4, '0', \STR_PAD_LEFT) . '}'
                : '\x' . \str_pad($hex, 2, '0', \STR_PAD_LEFT)
            ;
        }

        return $result;
    }

    /**
     * @param TokenInterface $token
     * @return bool
     */
    private function isAnonymous(TokenInterface $token): bool
    {
        $name = $token->getName();

        return \is_int($name) || $name === $token->getValue();
    }

    /**
     * @param ChannelInterface $channel
     * @return bool
     */
    private function isHiddenChannel(ChannelInterface $channel): bool
    {
        foreach ($this->info->hiddenChannels as $expected) {
            if ($expected->getName() === $channel->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param TokenInterface $token
     * @return string
     */
    private function name(TokenInterface $token): string
    {
        $channel = $token->getChannel();
        $name = $token->getName();

        if ($this->isHiddenChannel($channel)) {
            return $this->isAnonymous($token) ? '' : \sprintf(' (%s)', $token->getName());
        }

        return match (true) {
            \is_int($name), $name === $token->getValue() => ' (' . $channel->getName() . ')',
            default => \sprintf(' (%s:%s)', $channel->getName(), $name)
        };
    }
}
