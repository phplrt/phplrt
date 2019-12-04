<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Lexer\Driver\DriverInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Class Renderer
 */
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
     * @var array[]
     */
    private const DEFAULT_REPLACEMENTS = [
        ["\0", "\n", "\t"],
        ['\0', '\n', '\t'],
    ];

    /**
     * @var string
     */
    private const DEFAULT_OVERFLOW_SUFFIX = ' (%s+)';

    /**
     * @var int
     */
    private $length = self::DEFAULT_LENGTH;

    /**
     * @var string
     */
    private $wrap = self::DEFAULT_WRAP;

    /**
     * @var array[]
     */
    private $replacements = self::DEFAULT_REPLACEMENTS;

    /**
     * @var string
     */
    private $suffix = self::DEFAULT_OVERFLOW_SUFFIX;

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
        $value = $this->escape($this->inline($token->getValue()));

        if ($this->shouldBeShorten($value)) {
            return $this->shorten($value);
        }

        return $this->wrap . $this->replace($value) . $this->wrap;
    }

    /**
     * @param string $value
     * @return string
     */
    private function escape(string $value): string
    {
        return \addcslashes($value, $this->wrap);
    }

    /**
     * @param string $value
     * @return string
     */
    private function inline(string $value): string
    {
        return (string)(\preg_replace('/\h+/u', ' ', $value) ?? $value);
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
        return $this->wrap . $this->replace($value) . $this->wrap;
    }

    /**
     * @param string $value
     * @return string
     */
    private function replace(string $value): string
    {
        [$from, $to] = $this->replacements;

        return \str_replace($from, $to, $value);
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
