<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

use Laminas\Code\Generator\ValueGenerator;
use Phplrt\Compiler\Exception\UnsupportedEmbeddedLexerException;
use Phplrt\Compiler\Exception\UnsupportedReducerException;
use Phplrt\Compiler\Exception\UnsupportedRuleException;
use Phplrt\Lexer\Builder\Definition\Lexer\EmbeddedLexerInterface;
use Phplrt\Lexer\Builder\Definition\Lexer\PhpCodeEmbeddedLexer;
use Phplrt\Lexer\Builder\Definition\Lexer\RuntimeEmbeddedLexer;
use Phplrt\Parser\Builder\Definition\Reducer\CallableReducer;
use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;
use Phplrt\Parser\Builder\Definition\Reducer\ReducerInterface;
use Phplrt\Parser\Context;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Predicate;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;

/**
 * Writes the pieces a compiled grammar is built of down as PHP code.
 *
 * Everything a parser is built of is either a value the templates dump as it
 * is, or a definition that has to be spelled: the spelling is done here, so
 * that the templates are left with nothing but the shape of the file.
 */
final readonly class PhpCodePrinter
{
    /**
     * The class the constants standing for the tokens are declared in.
     *
     * The grammar is written down inside the very parser it belongs to, so a
     * token is reached without naming anything but the parser itself.
     *
     * @var non-empty-string
     */
    private const string SCOPE_TOKENS = 'self';

    /**
     * The prefix of the variable a lexer reading a fragment is written into.
     *
     * A fragment is named by the grammar and may be spelled in any way, so its
     * name is never a valid variable on its own.
     *
     * @var non-empty-string
     */
    private const string VARIABLE_PREFIX = '$state_';

    /**
     * The characters a single level of nesting is written with.
     *
     * @var non-empty-string
     */
    private const string INDENTATION = '    ';

    /**
     * Writes the given value down as the expression building it back.
     *
     * The value is written at the leftmost position, so that the code it is
     * written into is the only thing deciding where it is placed.
     */
    public function printValue(mixed $value, bool $multiline = true): string
    {
        $mode = $multiline
            ? ValueGenerator::OUTPUT_MULTIPLE_LINE
            : ValueGenerator::OUTPUT_SINGLE_LINE;

        return new ValueGenerator($value, outputMode: $mode)
            ->generate();
    }

    /**
     * Writes the variable a lexer reading the given fragment is put into.
     *
     * A fragment may be read by a lexer reading a fragment of its own, so a
     * lexer is named by the whole path leading to it rather than by the name
     * the grammar refers to it by.
     *
     * @return non-empty-string
     */
    public function printVariable(string $path): string
    {
        $name = \preg_replace('/\W++/', '_', $path);

        \assert($name !== null, 'A fragment is named by the characters a name is written of');

        return self::VARIABLE_PREFIX . $name;
    }

    /**
     * Writes the given rule of the grammar down as the expression building it.
     *
     * @param array<int, non-empty-string> $names a map of token ID and the name
     *        the lexer knows it by
     * @return non-empty-string
     * @throws UnsupportedRuleException in case of the rule cannot be written
     *         down
     */
    public function printRule(RuleInterface $rule, int $id, array $names = []): string
    {
        return match (true) {
            $rule instanceof Alternation => self::printExpression(Alternation::class, [
                self::printIdentifiers($rule->ruleIds),
            ]),
            $rule instanceof Concatenation => self::printExpression(Concatenation::class, [
                self::printIdentifiers($rule->ruleIds),
            ]),
            $rule instanceof Repetition => self::printExpression(Repetition::class, [
                (string) $rule->ruleId,
                (string) $rule->min,
                self::printRepetitions($rule->max),
            ]),
            $rule instanceof Optional => self::printExpression(Optional::class, [
                (string) $rule->ruleId,
            ]),
            $rule instanceof Predicate => self::printExpression(Predicate::class, [
                (string) $rule->ruleId,
                self::printBool($rule->isExpected),
            ]),
            $rule instanceof Lexeme => self::printExpression(Lexeme::class, [
                self::printToken($rule->tokenId, $names),
                self::printBool($rule->keep),
            ]),
            default => throw UnsupportedRuleException::becauseRuleCannotBeGenerated($id, $rule),
        };
    }

    /**
     * Writes the given reducer down as the callback converting a rule into a
     * node of the syntax tree.
     *
     * @return non-empty-string
     * @throws UnsupportedReducerException in case of the reducer cannot be
     *         written down
     */
    public function printReducer(ReducerInterface $reducer, int $id): string
    {
        $code = match (true) {
            $reducer instanceof PhpCodeReducer => $reducer->code,
            // A callback is a value of the process it has been built in and
            // cannot be spelled
            $reducer instanceof CallableReducer => throw UnsupportedReducerException::becauseReducerCannotBeGenerated($id, $reducer),
        };

        return \vsprintf("%sfunction (\\%s \$ctx, mixed \$children): mixed {\n%s\n}", [
            // A body referring to the object it belongs to is written as a
            // callback that has to be bound before it is called
            self::containsObjectReference($code) ? '' : 'static ',
            Context::class,
            $this->indent($code, first: true),
        ]);
    }

    /**
     * Writes the lexer reading the given fragment down as the expression
     * building it.
     *
     * @param non-empty-string $name
     * @return non-empty-string
     * @throws UnsupportedEmbeddedLexerException in case of the lexer cannot be
     *         written down
     */
    public function printEmbeddedLexer(EmbeddedLexerInterface $lexer, string $name): string
    {
        return match (true) {
            $lexer instanceof PhpCodeEmbeddedLexer => $lexer->code,
            // A lexer built at runtime is a value of the process it has been
            // built in and cannot be spelled
            $lexer instanceof RuntimeEmbeddedLexer => throw UnsupportedEmbeddedLexerException::becauseEmbeddedLexerCannotBeGenerated($name, $lexer),
        };
    }

    /**
     * Places the given code at the given level of nesting.
     *
     * A piece of code is written on its own and is placed by the code it is
     * written into, so the line it starts at is already placed by then.
     *
     * @param int<0, max> $level
     */
    public function indent(string $code, int $level = 1, bool $first = false): string
    {
        $prefix = \str_repeat(self::INDENTATION, $level);
        $lines = \explode("\n", $code);

        foreach ($lines as $index => $line) {
            if ($line === '' || ($index === 0 && !$first)) {
                continue;
            }

            $lines[$index] = $prefix . $line;
        }

        return \implode("\n", $lines);
    }

    /**
     * @param class-string $class
     * @param non-empty-list<string> $arguments
     * @return non-empty-string
     */
    private static function printExpression(string $class, array $arguments): string
    {
        return \sprintf('new \\%s(%s)', $class, \implode(', ', $arguments));
    }

    /**
     * @param non-empty-list<int> $ids
     */
    private static function printIdentifiers(array $ids): string
    {
        return \sprintf('[%s]', \implode(', ', $ids));
    }

    /**
     * @param array<int, non-empty-string> $names
     */
    private static function printToken(int $id, array $names): string
    {
        // A token written inside a rule is named by nothing, so there is no
        // constant to refer to it by
        if (!isset($names[$id])) {
            return (string) $id;
        }

        return self::SCOPE_TOKENS . '::' . $names[$id];
    }

    private static function printRepetitions(int|float $count): string
    {
        return \is_infinite($count) ? '\\INF' : (string) $count;
    }

    private static function printBool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    /**
     * Tells whether the given code refers to the object it belongs to.
     *
     * The code is read the way PHP reads it, so a "$this" written inside a
     * string or a comment is not mistaken for the variable.
     */
    private static function containsObjectReference(string $code): bool
    {
        foreach (\token_get_all('<?php ' . $code) as $token) {
            if (\is_array($token) && $token[0] === \T_VARIABLE && $token[1] === '$this') {
                return true;
            }
        }

        return false;
    }
}
