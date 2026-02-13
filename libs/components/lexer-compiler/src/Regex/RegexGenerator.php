<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Regex;

use Phplrt\Compiler\Lexer\Definition\RegexModifier;

/**
 * @template-covariant TResult of RegexGeneratorResult = RegexGeneratorResult
 *
 * @template-implements RegexGeneratorInterface<TResult>
 */
abstract readonly class RegexGenerator implements RegexGeneratorInterface
{
    /**
     * @var list<non-empty-string>
     */
    private const array ADDITIONAL_ESCAPED_CHARACTERS = ['#'];

    /**
     * Default PCRE delimiter.
     */
    final public const string DEFAULT_DELIMITER = '/';

    /**
     * List of characters that should be escaped in regex patterns.
     *
     * @var non-empty-string
     */
    private string $escapedCharacters;

    public function __construct(
        /**
         * @var non-empty-string
         */
        protected string $delimiter = self::DEFAULT_DELIMITER,
    ) {
        $this->escapedCharacters = $this->getEscapedCharacters($delimiter);
    }

    /**
     * @param non-empty-string $delimiter
     *
     * @return non-empty-string
     */
    private function getEscapedCharacters(string $delimiter): string
    {
        $result = $delimiter;

        foreach (self::ADDITIONAL_ESCAPED_CHARACTERS as $char) {
            if ($char !== $delimiter) {
                $result .= $char;
            }
        }

        return $result;
    }

    /**
     * @param non-empty-string $name
     *
     * @return non-empty-string
     */
    final protected function escapeValue(string $name): string
    {
        /** @var non-empty-string */
        return \preg_quote($name, $this->delimiter);
    }

    /**
     * @param non-empty-string $pattern
     *
     * @return non-empty-string
     */
    final protected function escapePattern(string $pattern): string
    {
        /** @var non-empty-string */
        return \addcslashes($pattern, $this->escapedCharacters);
    }

    /**
     * @param list<RegexModifier> $flags
     */
    private function flagsToString(array $flags): string
    {
        $result = [];

        foreach ($flags as $flag) {
            $result[] = $flag->value;
        }

        return \implode('', $result);
    }

    /**
     * @param list<RegexModifier> $flags
     *
     * @return non-empty-string
     */
    final protected function formatFullRegex(string $regex, array $flags): string
    {
        return $this->delimiter . $regex . $this->delimiter
            . $this->flagsToString($flags);
    }
}
