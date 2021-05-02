<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Exception\Internal;

/**
 * @internal ANSI is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Exception
 *
 * @psalm-type ANSIEnumType = ANSI::*
 */
final class ANSI
{
    public const CLR_BLACK        = "\033[0;30m";
    public const CLR_RED          = "\033[1;31m";
    public const CLR_GREEN        = "\033[1;32m";
    public const CLR_YELLOW       = "\033[1;33m";
    public const CLR_BLUE         = "\033[1;34m";
    public const CLR_MAGENTA      = "\033[1;35m";
    public const CLR_CYAN         = "\033[1;36m";
    public const CLR_WHITE        = "\033[1;37m";
    public const CLR_GRAY         = "\033[0;37m";
    public const CLR_DARK_RED     = "\033[0;31m";
    public const CLR_DARK_GREEN   = "\033[0;32m";
    public const CLR_DARK_YELLOW  = "\033[0;33m";
    public const CLR_DARK_BLUE    = "\033[0;34m";
    public const CLR_DARK_MAGENTA = "\033[0;35m";
    public const CLR_DARK_CYAN    = "\033[0;36m";
    public const CLR_DARK_WHITE   = "\033[0;37m";
    public const CLR_DARK_GRAY    = "\033[1;30m";
    public const BG_BLACK         = "\033[40m";
    public const BG_RED           = "\033[41m";
    public const BG_GREEN         = "\033[42m";
    public const BG_YELLOW        = "\033[43m";
    public const BG_BLUE          = "\033[44m";
    public const BG_MAGENTA       = "\033[45m";
    public const BG_CYAN          = "\033[46m";
    public const BG_WHITE         = "\033[47m";
    public const SEQ_BOLD         = "\033[1m";
    public const SEQ_ITALIC       = "\033[3m";
    public const SEQ_RESET        = "\033[0m";
}
