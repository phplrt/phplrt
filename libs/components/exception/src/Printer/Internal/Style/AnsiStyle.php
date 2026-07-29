<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer\Internal\Style;

use Phplrt\Exception\Printer\Level;

/**
 * Decorates the output by the ANSI escape sequences supported by the terminals.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Exception
 */
final readonly class AnsiStyle implements StyleInterface
{
    /**
     * @var non-empty-string
     */
    private const string DELIMITER = '␤';

    /**
     * @var non-empty-string
     */
    private const string SEQUENCE_ERROR = '31';

    /**
     * @var non-empty-string
     */
    private const string SEQUENCE_WARNING = '33';

    /**
     * @var non-empty-string
     */
    private const string SEQUENCE_DEBUG = '1';

    /**
     * @var non-empty-string
     */
    private const string SEQUENCE_DIMMED = '90';

    /**
     * @var non-empty-string
     */
    private const string SEQUENCE_FRAME = '94';

    public function paint(string $value, Level $level): string
    {
        return $this->wrap($value, $this->getSequence($level));
    }

    public function dim(string $value): string
    {
        return $this->wrap($value, self::SEQUENCE_DIMMED);
    }

    public function frame(string $value): string
    {
        return $this->wrap($value, self::SEQUENCE_FRAME);
    }

    public function getDelimiter(): string
    {
        return self::DELIMITER;
    }

    /**
     * @return non-empty-string
     */
    private function getSequence(Level $level): string
    {
        return match ($level) {
            Level::Error => self::SEQUENCE_ERROR,
            Level::Warning => self::SEQUENCE_WARNING,
            Level::Debug => self::SEQUENCE_DEBUG,
        };
    }

    /**
     * @param non-empty-string $sequence
     */
    private function wrap(string $value, string $sequence): string
    {
        return $value === '' ? '' : \sprintf("\e[%sm%s\e[0m", $sequence, $value);
    }
}
