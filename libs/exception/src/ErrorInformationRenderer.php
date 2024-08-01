<?php

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Position\Position;

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

    private PositionInterface $position;

    private LineReader $reader;

    private string $sourceTemplate = self::DEFAULT_SOURCE_TEMPLATE;

    private string $errorTemplate = self::DEFAULT_ERROR_TEMPLATE;

    private string $highlightChar = self::DEFAULT_HIGHLIGHT_CHAR;

    public function __construct(ReadableInterface $source, private TokenInterface $token)
    {
        $this->position = Position::fromOffset($source, $this->token->getOffset());

        $this->reader = new LineReader($source);
    }

    public function render(): string
    {
        return \implode(\PHP_EOL, [
            $this->renderErrorLine(),
            $this->renderErrorHighlighter(),
        ]);
    }

    public function renderErrorLine(): string
    {
        $line = $this->position->getLine();

        $message = $this->highlightError($this->reader->readLine($line));

        return $this->format($message, $line . '.');
    }

    private function highlightError(string $text): string
    {
        if (!$this->isAnsiAllowed()) {
            return $text;
        }

        return \vsprintf($this->errorTemplate, [
            \substr($text, 0, $this->from()),
            \substr($text, $this->from(), $this->length()),
            \substr($text, $this->to()),
        ]);
    }

    private function isAnsiAllowed(): bool
    {
        return \PHP_SAPI === 'cli';
    }

    private function from(): int
    {
        return $this->position->getColumn() - 1;
    }

    private function length(): int
    {
        return $this->token->getBytes();
    }

    private function to(): int
    {
        return $this->from() + $this->length();
    }

    private function format(string $message, string $line = ''): string
    {
        return \sprintf($this->sourceTemplate, $line, $message);
    }

    public function renderErrorHighlighter(): string
    {
        $prefix = \str_repeat(' ', $this->from());

        $highlight = \str_repeat($this->highlightChar, $this->length());

        return $this->format($prefix . $highlight);
    }
}
