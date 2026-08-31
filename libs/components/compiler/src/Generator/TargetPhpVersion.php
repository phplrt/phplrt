<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

enum TargetPhpVersion: int
{
    case Php86 = 80600;
    case Php85 = 80500;
    case Php84 = 80400;
    case Php83 = 80300;
    case Php82 = 80200;
    case Php81 = 80100;

    public static function fromString(string $version): self
    {
        $segments = \explode('.', \trim($version));
        $segments[1] ??= '0';

        /** @var array{string, string} $segments */
        return match (\trim($segments[0])) {
            '8' => match (\trim($segments[1])) {
                '1' => self::Php81,
                '2' => self::Php82,
                '3' => self::Php83,
                '4' => self::Php84,
                '5' => self::Php85,
                default => self::Php86,
            },
            default => throw new \InvalidArgumentException(\sprintf(
                'The target PHP major version must be >= 8, "%s" given',
                $segments[0],
            )),
        };
    }

    public static function current(): self
    {
        return match (true) {
            \PHP_VERSION_ID >= self::Php86->value => self::Php86,
            \PHP_VERSION_ID >= self::Php85->value => self::Php85,
            \PHP_VERSION_ID >= self::Php84->value => self::Php84,
            \PHP_VERSION_ID >= self::Php83->value => self::Php83,
            \PHP_VERSION_ID >= self::Php82->value => self::Php82,
            // \PHP_VERSION_ID >= self::Php81->value => self::Php81,
            default => throw new \RuntimeException('Unsupported PHP version target'),
        };
    }
}
