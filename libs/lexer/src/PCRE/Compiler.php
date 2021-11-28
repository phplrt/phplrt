<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\PCRE;

use JetBrains\PhpStorm\Language;
use Phplrt\Lexer\Exception\CompilationException;
use Phplrt\Lexer\LexerCreateInfo;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Lexer
 */
final class Compiler
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
     * @var string
     */
    private readonly string $flags;

    /**
     * @var non-empty-string
     */
    private readonly string $delimiter;

    /**
     * @param LexerCreateInfo $info
     */
    public function __construct(
        private readonly LexerCreateInfo $info,
    ) {
        $this->flags = Flag::toString($this->info->pcre->flags);
        $this->delimiter = $this->info->pcre->delimiter;
    }

    /**
     * @param array<non-empty-string, non-empty-string> $tokens
     * @param array<non-empty-string, non-empty-string|int> $mappings
     * @return non-empty-string
     * @throws CompilationException
     */
    public function compile(array $tokens, array $mappings): string
    {
        $chunks = [];

        foreach ($tokens as $name => $pcre) {
            $chunks[] = $chunk = $this->implodeToken(
                $this->escapeName($name),
                $this->escapePattern($pcre)
            );

            /** @psalm-suppress ArgumentTypeCoercion */
            $this->info->debug and $this->test($chunk, [$mappings[$name] ?? $name, $pcre]);
        }

        $body = $this->implodeAllTokens($chunks);

        $this->info->debug and $this->test($body);

        return $this->create($body);
    }

    /**
     * @param array<non-empty-string> $chunks
     * @return non-empty-string
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    protected function implodeAllTokens(array $chunks): string
    {
        return \sprintf(self::FORMAT_BODY, \implode('|', $chunks));
    }

    /**
     * @param non-empty-string $name
     * @param non-empty-string $pattern
     * @return non-empty-string
     *
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MoreSpecificReturnType
     */
    protected function implodeToken(string $name, string $pattern): string
    {
        return \sprintf(self::FORMAT_MARKER, $pattern, $name);
    }

    /**
     * @param non-empty-string $name
     * @return non-empty-string
     *
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MoreSpecificReturnType
     */
    protected function escapeName(string $name): string
    {
        return \preg_quote($name, $this->delimiter);
    }

    /**
     * @param non-empty-string $pattern
     * @return non-empty-string
     *
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MoreSpecificReturnType
     */
    protected function escapePattern(string $pattern): string
    {
        return \addcslashes($pattern, $this->delimiter);
    }

    /**
     * @param non-empty-string $pattern
     * @param array{non-empty-string, non-empty-string}|null $token
     * @return void
     * @throws CompilationException
     *
     * @psalm-suppress UnusedFunctionCall Preg match contains side effect (provide errors)
     */
    public function test(string $pattern, array $token = null): void
    {
        \error_clear_last();

        @\preg_match_all($this->create($pattern), '');

        if ($error = \error_get_last()) {
            $message = self::formatException((string)$error['message']);

            if ($token !== null) {
                $message .= \sprintf(' in %s = %s token definition', ...$token);
            }

            throw new CompilationException($message);
        }
    }

    /**
     * @param non-empty-string $pcre
     * @return non-empty-string
     */
    protected function create(#[Language('RegExp')] string $pcre): string
    {
        return $this->delimiter . $pcre . $this->delimiter . $this->flags;
    }

    /**
     * @param string $message
     * @return string
     */
    protected static function formatException(string $message): string
    {
        $message = \str_replace('Compilation failed: ', '', $message);
        $message = (string)\preg_replace('/([\w_]+\(\):\h+)/', '', $message);
        $message = (string)\preg_replace('/\h*at\h+offset\h+\d+/', '', $message);

        return \ucfirst($message);
    }
}
