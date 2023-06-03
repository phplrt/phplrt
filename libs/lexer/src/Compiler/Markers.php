<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Compiler;

class Markers extends PCRECompiler
{
    /**
     * @var string
     */
    private const FORMAT_MARKER = '(?:(?:%s)(*MARK:%s))';

    /**
     * @var string
     */
    private const FORMAT_BODY = '\\G(?|%s)';

    /**
     * @param array<array-key, string> $chunks
     * @return non-empty-string
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    protected function buildTokens(array $chunks): string
    {
        return \sprintf(self::FORMAT_BODY, \implode('|', $chunks));
    }

    /**
     * @param non-empty-string $name
     * @param non-empty-string $pattern
     * @return non-empty-string
     */
    protected function buildToken(string $name, string $pattern): string
    {
        return \sprintf(self::FORMAT_MARKER, $pattern, $name);
    }
}
