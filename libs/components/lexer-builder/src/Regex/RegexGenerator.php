<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Regex;

use Phplrt\Lexer\Builder\Definition\RegexModifier;
use Phplrt\Lexer\Builder\Internal\PatternFormatter;

/**
 * @readonly
 */
abstract class RegexGenerator implements RegexGeneratorInterface
{
    /**
     * Default PCRE delimiter.
     */
    final public const DEFAULT_DELIMITER = '/';

    public function __construct(
        /**
         * @var non-empty-string
         */
        protected readonly string $delimiter = self::DEFAULT_DELIMITER,
    ) {}

    /**
     * @param non-empty-string $name
     * @return non-empty-string
     */
    final protected function escapeValue(string $name): string
    {
        /** @var non-empty-string */
        return \preg_quote($name, $this->delimiter);
    }

    /**
     * @param non-empty-string $pattern
     * @return non-empty-string
     */
    final protected function escapePattern(string $pattern): string
    {
        return PatternFormatter::escapeDelimiter($pattern, $this->delimiter);
    }

    /**
     * Writes the given expression down between the delimiters, along with
     * the flags it is compiled with.
     *
     * @param list<RegexModifier> $flags
     * @return non-empty-string
     */
    final protected function formatFullRegex(string $regex, array $flags): string
    {
        return PatternFormatter::formatPattern($regex, $flags, $this->delimiter);
    }
}
