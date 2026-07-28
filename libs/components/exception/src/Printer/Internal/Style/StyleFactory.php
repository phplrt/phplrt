<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer\Internal\Style;

/**
 * Decides how the output is decorated in case the caller has no opinion on it.
 *
 * The decision is made the way the other command line tools make it: the
 * escape sequences are printed only while the output is a terminal that has
 * not been asked to stay plain.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Exception
 */
final readonly class StyleFactory
{
    /**
     * @param bool|null $colors {@see true} to decorate the output by the
     *        escape sequences, {@see false} to keep it plain or {@see null}
     *        to decide it by the output itself
     */
    public static function create(?bool $colors = null): StyleInterface
    {
        return ($colors ?? self::isSupported())
            ? new AnsiStyle()
            : new PlainStyle();
    }

    /**
     * Returns {@see true} in case the output understands the escape sequences.
     */
    private static function isSupported(): bool
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
}
