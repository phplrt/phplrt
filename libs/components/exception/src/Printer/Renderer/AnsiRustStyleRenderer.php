<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer\Renderer;

use Phplrt\Exception\Analysis\FailureLevel;

/**
 * Prints the diagnostics decorated by the ANSI escape sequences supported by
 * the terminals.
 *
 * @readonly
 */
final class AnsiRustStyleRenderer extends RustStyleRenderer
{
    /**
     * @var non-empty-string
     */
    private const DELIMITER = '␤';

    /**
     * @var non-empty-string
     */
    private const SEQUENCE_ERROR = '31';

    /**
     * @var non-empty-string
     */
    private const SEQUENCE_WARNING = '33';

    /**
     * @var non-empty-string
     */
    private const SEQUENCE_DEBUG = '1';

    /**
     * @var non-empty-string
     */
    private const SEQUENCE_DIMMED = '90';

    /**
     * @var non-empty-string
     */
    private const SEQUENCE_FRAME = '94';

    /**
     * Returns {@see true} in case the output understands the escape sequences.
     *
     * The decision is made the way the other command line tools make it: the
     * escape sequences are printed only while the output is a terminal that
     * has not been asked to stay plain.
     *
     * @api
     */
    public static function isSupported(): bool
    {
        // https://no-color.org
        if (self::findEnv('NO_COLOR') !== null) {
            return false;
        }

        if (self::findEnv('FORCE_COLOR') !== null) {
            return true;
        }

        // Something that is not a terminal (a file or a pipe) is read by
        // somebody who has no idea what the escape sequences are
        if (!\defined('STDOUT') || !\stream_isatty(\STDOUT)) {
            return false;
        }

        // A terminal telling it understands nothing is taken at its word
        if (self::findEnv('TERM') === 'dumb') {
            return false;
        }

        return \PHP_OS_FAMILY !== 'Windows' || self::isSupportedByWindows();
    }

    protected function printError(string $value, FailureLevel $level): string
    {
        return $this->wrap($value, match ($level) {
            FailureLevel::Error => self::SEQUENCE_ERROR,
            FailureLevel::Warning => self::SEQUENCE_WARNING,
            FailureLevel::Debug => self::SEQUENCE_DEBUG,
        });
    }

    protected function printFrame(string $value): string
    {
        return $this->wrap($value, self::SEQUENCE_FRAME);
    }

    protected function printDelimiter(): string
    {
        return $this->wrap(self::DELIMITER, self::SEQUENCE_DIMMED);
    }

    /**
     * The console of Windows understands the escape sequences only in case it
     * has been switched into the corresponding mode, which the terminals do
     * on their own.
     */
    private static function isSupportedByWindows(): bool
    {
        if (\function_exists('sapi_windows_vt100_support')
            && @\sapi_windows_vt100_support(\STDOUT)
        ) {
            return true;
        }

        return self::findEnv('WT_SESSION') !== null
            || self::findEnv('ANSICON') !== null
            || self::findEnv('ConEmuANSI') === 'ON'
            || \str_starts_with(self::findEnv('TERM') ?? '', 'xterm');
    }

    /**
     * Returns the value of the environment variable or {@see null} in case it
     * has not been set at all.
     *
     * @param non-empty-string $name
     * @return non-empty-string|null
     */
    private static function findEnv(string $name): ?string
    {
        $result = \getenv($name);

        return $result === false || $result === '' ? null : $result;
    }

    /**
     * @param non-empty-string $sequence
     */
    private function wrap(string $value, string $sequence): string
    {
        return $value === '' ? '' : \sprintf("\e[%sm%s\e[0m", $sequence, $value);
    }
}
