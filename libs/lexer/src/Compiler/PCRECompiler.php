<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Compiler;

use Phplrt\Lexer\Exception\CompilationException;

/**
 * @deprecated since phplrt 3.6 and will be removed in 4.0.
 *
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Lexer
 */
abstract class PCRECompiler implements CompilerInterface
{
    /**
     * If this modifier is set, letters in the pattern match both upper
     * and lower case letters.
     *
     * @var string
     */
    public const FLAG_CASELESS = 'i';

    /**
     * By default, PCRE treats the subject string as consisting of a single
     * "line" of characters (even if it actually contains several newlines).
     *
     * The "start of line" metacharacter (^) matches only at the start of the
     * string, while the "end of line" metacharacter ($) matches only at the
     * end of the string, or before a terminating newline (unless D modifier
     * is set). This is the same as Perl. When this modifier is set, the
     * "start of line" and "end of line" constructs match immediately following
     * or immediately before any newline in the subject string, respectively,
     * as well as at the very start and end. This is equivalent to Perl's /m
     * modifier. If there are no "\n" characters in a subject string, or no
     * occurrences of ^ or $ in a pattern, setting this modifier has no effect.
     *
     * @var string
     */
    public const FLAG_MULTILINE = 'm';

    /**
     * If this modifier is set, a dot metacharacter in the pattern matches
     * all characters, including newlines. Without it, newlines are excluded.
     * This modifier is equivalent to Perl's /s modifier. A negative class
     * such as [^a] always matches a newline character, independent of the
     * setting of this modifier.
     *
     * @var string
     */
    public const FLAG_DOTALL = 's';

    /**
     * If this modifier is set, whitespace data characters in the pattern are
     * totally ignored except when escaped or inside a character class, and
     * characters between an unescaped # outside a character class and the
     * next newline character, inclusive, are also ignored. This is equivalent
     * to Perl's /x modifier, and makes it possible to include commentary
     * inside complicated patterns. Note, however, that this applies only to
     * data characters. Whitespace characters may never appear within special
     * character sequences in a pattern, for example within the sequence
     * (?( which introduces a conditional subpattern.
     *
     * @var string
     */
    public const FLAG_EXTENDED = 'x';

    /**
     * If this modifier is set, the pattern is forced to be "anchored", that is,
     * it is constrained to match only at the start of the string which is
     * being searched (the "subject string"). This effect can also be achieved
     * by appropriate constructs in the pattern itself, which is the only way
     * to do it in Perl.
     *
     * @var string
     */
    public const FLAG_ANCHORED = 'A';

    /**
     * If this modifier is set, a dollar metacharacter in the pattern matches
     * only at the end of the subject string. Without this modifier, a dollar
     * also matches immediately before the final character if it is a newline
     * (but not before any other newlines). This modifier is ignored if m
     * modifier is set. There is no equivalent to this modifier in Perl.
     *
     * @var string
     */
    public const FLAG_DOLLAR_ENDONLY = 'D';

    /**
     * When a pattern is going to be used several times, it is worth spending
     * more time analyzing it in order to speed up the time taken for matching.
     * If this modifier is set, then this extra analysis is performed. At
     * present, studying a pattern is useful only for non-anchored patterns
     * that do not have a single fixed starting character.
     *
     * @var string
     */
    public const FLAG_COMPILED = 'S';

    /**
     * This modifier inverts the "greediness" of the quantifiers so that they
     * are not greedy by default, but become greedy if followed by ?. It is
     * not compatible with Perl. It can also be set by a (?U) modifier setting
     * within the pattern or by a question mark behind a quantifier (e.g. .*?).
     *
     * @var string
     */
    public const FLAG_UNGREEDY = 'U';

    /**
     * This modifier turns on additional functionality of PCRE that is
     * incompatible with Perl. Any backslash in a pattern that is followed by
     * a letter that has no special meaning causes an error, thus reserving
     * these combinations for future expansion. By default, as in Perl, a
     * backslash followed by a letter with no special meaning is treated as a
     * literal. There are at present no other features controlled by this
     * modifier.
     *
     * @var string
     */
    public const FLAG_EXTRA = 'X';

    /**
     * The (?J) internal option setting changes the local PCRE_DUPNAMES option.
     * Allow duplicate names for subpatterns. As of PHP 7.2.0 J is supported
     * as modifier as well.
     *
     * @var string
     */
    public const FLAG_INFO_JCHANGED = 'X';

    /**
     * This modifier turns on additional functionality of PCRE that is
     * incompatible with Perl. Pattern and subject strings are treated as UTF-8.
     * An invalid subject will cause the preg_* function to match nothing; an
     * invalid pattern will trigger an error of level E_WARNING. Five and six
     * octet UTF-8 sequences are regarded as invalid since PHP 5.3.4
     * (resp. PCRE 7.3 2007-08-28); formerly those have been regarded as
     * valid UTF-8.
     *
     * @var string
     */
    public const FLAG_UTF8 = 'u';

    /**
     * Default pcre delimiter.
     *
     * @var string
     */
    protected const DEFAULT_DELIMITER = '/';

    /**
     * @var non-empty-array<PCRECompiler::FLAG_*>
     */
    protected const DEFAULT_FLAGS = [
        self::FLAG_COMPILED,
        self::FLAG_DOTALL,
        self::FLAG_UTF8,
        self::FLAG_MULTILINE,
    ];

    /**
     * @var array<PCRECompiler::FLAG_*>
     */
    protected array $flags = [];

    protected string $delimiter = self::DEFAULT_DELIMITER;

    private bool $debug = false;

    /**
     * @param array<PCRECompiler::FLAG_*>|null $flags
     * @param bool|null $debug
     */
    public function __construct(array $flags = null, bool $debug = null)
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->flags = $flags ?? self::DEFAULT_FLAGS;

        if ($debug === null) {
            // Hack: Enable debug mode if assertions is enabled
            \assert($this->debug = true);
        } else {
            $this->debug = $debug;
        }
    }

    /**
     * @param array<non-empty-string, non-empty-string> $tokens
     * @return non-empty-string
     * @throws CompilationException
     */
    public function compile(array $tokens): string
    {
        $body = $this->buildTokens($this->buildChunks($tokens));

        $this->test($body);

        return $this->wrap($body);
    }

    /**
     * @param array<non-empty-string> $chunks
     * @return non-empty-string
     */
    abstract protected function buildTokens(array $chunks): string;

    /**
     * @param array<non-empty-string, non-empty-string> $tokens
     * @return array<non-empty-string>
     */
    private function buildChunks(array $tokens): array
    {
        $chunks = [];

        foreach ($tokens as $name => $pcre) {
            $chunks[] = $chunk = $this->buildToken($this->name($name), $this->pattern($pcre));

            $this->test($chunk, $name);
        }

        return $chunks;
    }

    /**
     * @param non-empty-string $name
     * @param non-empty-string $pattern
     * @return non-empty-string
     */
    abstract protected function buildToken(string $name, string $pattern): string;

    /**
     * @param non-empty-string $name
     * @return non-empty-string
     */
    protected function name(string $name): string
    {
        /** @var non-empty-string */
        return \preg_quote($name, $this->delimiter);
    }

    /**
     * @param non-empty-string $pattern
     * @return non-empty-string
     */
    protected function pattern(string $pattern): string
    {
        /** @var non-empty-string */
        return \addcslashes($pattern, $this->delimiter);
    }

    /**
     * @param non-empty-string $pattern
     * @param non-empty-string|null $original
     */
    protected function test(string $pattern, string $original = null): void
    {
        if ($this->debug) {
            \error_clear_last();

            $flags = \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE;

            @\preg_match_all($this->wrap($pattern), '', $matches, $flags);

            if ($error = \error_get_last()) {
                throw new CompilationException($this->formatException($error['message'], $original));
            }
        }
    }

    /**
     * @param non-empty-string $pcre
     * @return non-empty-string
     */
    protected function wrap(string $pcre): string
    {
        return $this->delimiter . $pcre . $this->delimiter . \implode('', $this->flags);
    }

    protected function formatException(string $message, string $token = null): string
    {
        $suffix = \sprintf(' in %s token definition', $token ?? '<unknown>');

        $message = \str_replace('Compilation failed: ', '', $message);
        $message = \preg_replace('/([\w_]+\(\):\h+)/', '', $message);
        $message = \preg_replace('/\h*at\h+offset\h+\d+/', '', $message);

        return \ucfirst($message) . (\is_string($token) ? $suffix : '');
    }
}
