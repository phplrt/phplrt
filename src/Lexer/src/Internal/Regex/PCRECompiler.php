<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Internal\Regex;

use Phplrt\Lexer\Exception\CompilationException;

/**
 * @internal PCRECompiler is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Lexer
 *
 * @psalm-import-type FlagType from Flag
 */
abstract class PCRECompiler implements CompilerInterface
{
    /**
     * Default pcre delimiter.
     *
     * @var string
     */
    protected const DEFAULT_DELIMITER = '/';

    /**
     * @var array<FlagType>
     */
    protected const DEFAULT_FLAGS = [
        Flag::FLAG_COMPILED,
        Flag::FLAG_DOTALL,
        Flag::FLAG_UTF8,
        Flag::FLAG_MULTILINE,
    ];

    /**
     * @var array<FlagType>
     */
    protected array $flags;

    /**
     * @var string
     */
    protected string $delimiter = self::DEFAULT_DELIMITER;

    /**
     * @var bool
     */
    private bool $debug = false;

    /**
     * @param array<FlagType>|null $flags
     * @param bool|null $debug
     */
    public function __construct(array $flags = null, bool $debug = null)
    {
        $this->flags = $flags ?? self::DEFAULT_FLAGS;

        if ($debug === null) {
            // Hack: Enable debug mode if assertions is enabled
            assert($this->debug = true);
        } else {
            $this->debug = $debug;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function compile(array $tokens): string
    {
        $body = $this->implodeAllTokens($this->implodeChunks($tokens));

        $this->debug and $this->test($body);

        return $this->wrap($body);
    }

    /**
     * @param array<string> $chunks
     * @return string
     */
    abstract protected function implodeAllTokens(array $chunks): string;

    /**
     * @param array<string, string> $tokens
     * @return array<string>
     */
    private function implodeChunks(array $tokens): array
    {
        $chunks = [];

        foreach ($tokens as $name => $pcre) {
            $chunks[] = $chunk = $this->implodeToken(
                $this->escapeTokenName($name),
                $this->escapeTokenPattern($pcre)
            );

            $this->debug and $this->test($chunk, $name);
        }

        return $chunks;
    }

    /**
     * @param string $name
     * @param string $pattern
     * @return string
     */
    abstract protected function implodeToken(string $name, string $pattern): string;

    /**
     * @param string $name
     * @return string
     */
    protected function escapeTokenName(string $name): string
    {
        return \preg_quote($name, $this->delimiter);
    }

    /**
     * @param string $pattern
     * @return string
     */
    protected function escapeTokenPattern(string $pattern): string
    {
        return \addcslashes($pattern, $this->delimiter);
    }

    /**
     * @param string $pattern
     * @param string|null $original
     * @return void
     */
    protected function test(string $pattern, string $original = null): void
    {
        \error_clear_last();

        $flags = \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE;

        @\preg_match_all($this->wrap($pattern), '', $matches, $flags);

        if ($error = \error_get_last()) {
            throw new CompilationException($this->formatException($error['message'], $original));
        }
    }

    /**
     * @param string $pcre
     * @return string
     */
    protected function wrap(string $pcre): string
    {
        return $this->delimiter . $pcre . $this->delimiter . \implode('', $this->flags);
    }

    /**
     * @param string $message
     * @param string|null $token
     * @return string
     */
    protected function formatException(string $message, string $token = null): string
    {
        $suffix = \sprintf(' in %s token definition', $token);

        $message = \str_replace('Compilation failed: ', '', $message);
        $message = (string)\preg_replace('/([\w_]+\(\):\h+)/', '', $message);
        $message = (string)\preg_replace('/\h*at\h+offset\h+\d+/', '', $message);

        return \ucfirst($message) . (\is_string($token) ? $suffix : '');
    }
}
