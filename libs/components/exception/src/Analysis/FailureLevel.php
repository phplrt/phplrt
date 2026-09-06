<?php

declare(strict_types=1);

namespace Phplrt\Exception\Analysis;

/**
 * The severity of the error.
 */
enum FailureLevel: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Debug = 'debug';

    /**
     * The severity of an error that tells nothing about the one of its own.
     *
     * @var self
     */
    public const DEFAULT = self::Error;

    /**
     * Returns the severity the given error tells about itself, or the default
     * one in case it tells nothing.
     */
    public static function fromException(\Throwable $e): self
    {
        if (!$e instanceof \ErrorException) {
            return self::DEFAULT;
        }

        return self::fromSeverity($e->getSeverity());
    }

    public static function fromSeverity(int $severity): self
    {
        return match ($severity) {
            \E_WARNING,
            \E_CORE_WARNING,
            \E_COMPILE_WARNING,
            \E_USER_WARNING => self::Warning,
            \E_NOTICE,
            \E_USER_NOTICE,
            \E_DEPRECATED,
            \E_USER_DEPRECATED => self::Debug,
            default => self::DEFAULT,
        };
    }
}
