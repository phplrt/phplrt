<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\PCRE;

final class RegExpCreateInfo
{
    /**
     * List of default PCRE {@see Flag}.
     *
     * @link https://www.php.net/manual/en/reference.pcre.pattern.modifiers.php
     *
     * @var Flag
     */
    public const DEFAULT_PCRE_FLAGS = [
        Flag::COMPILED,
        Flag::DOTALL,
        Flag::UTF8,
        Flag::MULTILINE,
    ];

    /**
     * Default PCRE delimiter.
     *
     * @var string
     */
    public const DEFAULT_PCRE_DELIMITER = '/';

    /**
     * @var array<Flag>
     */
    public readonly array $flags;

    /**
     * @param iterable<Flag> $flags
     * @param non-empty-string $delimiter
     */
    public function __construct(
        iterable $flags = self::DEFAULT_PCRE_FLAGS,
        public readonly string $delimiter = self::DEFAULT_PCRE_DELIMITER,
    ) {
        $this->flags = [...$flags];

        assert(\strlen($this->delimiter) === 1, new \InvalidArgumentException(
            'PCRE delimiter MUST contain only 1 char, but ' . \strlen($this->delimiter) . ' chars passed'
        ));
    }
}
