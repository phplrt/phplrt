<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Position\Position;
use Phplrt\Position\PositionInterface;

/**
 * Class ErrorInformationRenderer
 */
class ErrorInformationRenderer
{
    /**
     * @var string
     */
    public const DEFAULT_SOURCE_TEMPLATE = ' %4s | %s';

    /**
     * @var string
     */
    public const DEFAULT_ERROR_TEMPLATE = "%s\033[1;37m\033[41m%s\033[0m%s";

    /**
     * @var string
     */
    public const DEFAULT_HIGHLIGHT_CHAR = '^';

    /**
     * @var PositionInterface
     */
    private PositionInterface $position;

    /**
     * @var LineReader
     */
    private LineReader $reader;

    /**
     * @var string
     */
    private string $sourceTemplate = self::DEFAULT_SOURCE_TEMPLATE;

    /**
     * @var string
     */
    private string $errorTemplate = self::DEFAULT_ERROR_TEMPLATE;

    /**
     * @var string
     */
    private string $highlightChar = self::DEFAULT_HIGHLIGHT_CHAR;

    /**
     * @var TokenInterface
     */
    private TokenInterface $token;

    /**
     * ErrorInformationRenderer constructor.
     *
     * @param ReadableInterface $source
     * @param TokenInterface $token
     */
    public function __construct(ReadableInterface $source, TokenInterface $token)
    {
        $this->token = $token;

        $this->position = Position::fromOffset($source, $token->getOffset());

        $this->reader = new LineReader($source);
    }

    /**
     * @return string
     */
    public function render(): string
    {
        return \implode(\PHP_EOL, [
            $this->renderErrorLine(),
            $this->renderErrorHighlighter(),
        ]);
    }

    /**
     * @return string
     */
    public function renderErrorLine(): string
    {
        $line = $this->position->getLine();

        $message = $this->highlightError($this->reader->readLine($line));

        return $this->format($message, $line . '.');
    }

    /**
     * @param string $text
     * @return string
     */
    private function highlightError(string $text): string
    {
        if (! $this->isAnsiAllowed()) {
            return $text;
        }

        return \vsprintf($this->errorTemplate, [
            \substr($text, 0, $this->from()),
            \substr($text, $this->from(), $this->length()),
            \substr($text, $this->to()),
        ]);
    }

    /**
     * @return bool
     */
    private function isAnsiAllowed(): bool
    {
        return \PHP_SAPI === 'cli';
    }

    /**
     * @return int
     */
    private function from(): int
    {
        return $this->position->getColumn() - 1;
    }

    /**
     * @return int
     */
    private function length(): int
    {
        return $this->token->getBytes();
    }

    /**
     * @return int
     */
    private function to(): int
    {
        return $this->from() + $this->length();
    }

    /**
     * @param string $message
     * @param string $line
     * @return string
     */
    private function format(string $message, string $line = ''): string
    {
        return \sprintf($this->sourceTemplate, $line, $message);
    }

    /**
     * @return string
     */
    public function renderErrorHighlighter(): string
    {
        $prefix = \str_repeat(' ', $this->from());

        $highlight = \str_repeat($this->highlightChar, $this->length());

        return $this->format($prefix . $highlight);
    }
}
