<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Syntax\PP3;

use Phplrt\Compiler\Node\Declaration\IncludeDeclaration;
use Phplrt\Compiler\Node\Declaration\LexerDeclaration;
use Phplrt\Compiler\Node\Declaration\PragmaDeclaration;
use Phplrt\Compiler\Node\Declaration\RuleDeclaration;
use Phplrt\Compiler\Node\Declaration\TokenAction;
use Phplrt\Compiler\Node\Declaration\TokenDeclaration;
use Phplrt\Compiler\Node\Reducer\CodeReducer;
use Phplrt\Compiler\Node\Reducer\Reducer;
use Phplrt\Compiler\Node\Statement\Alternation;
use Phplrt\Compiler\Node\Statement\Concatenation;
use Phplrt\Compiler\Node\Statement\InlinePattern;
use Phplrt\Compiler\Node\Statement\Quantifier;
use Phplrt\Compiler\Node\Statement\Repetition;
use Phplrt\Compiler\Node\Statement\RuleReference;
use Phplrt\Compiler\Node\Statement\Statement;
use Phplrt\Compiler\Node\Statement\TokenReference;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Token\Token;
use Phplrt\Lexer\Token\TokenEmbedding;
use Phplrt\Parser\Context;
use Phplrt\Parser\Exception\UnexpectedTokenException;

/**
 * Builds the declarations a PP3 grammar file is written of.
 *
 * The grammar of the format says what a file is made of, while every node it is
 * read into is built here: a grammar file is written of what it describes rather
 * than of the way the description is put together.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Compiler
 */
final readonly class PP3NodeFactory
{
    /**
     * The number of characters a single level of nesting is written with.
     *
     * @var int<1, max>
     */
    private const int NESTING_SIZE = 4;

    /**
     * What the actions of a token declaration are written after.
     *
     * @var non-empty-string
     */
    private const string ACTION_ARROW = '->';

    /**
     * A name of a state, a token, an action or a rule.
     *
     * @var non-empty-string
     */
    private const string PATTERN_WORD = '[a-zA-Z_][a-zA-Z0-9_]*+';

    /**
     * A single thing a token does to the reading, written the way a call is
     * written.
     *
     * The declaration captures everything a token does as a single value, so
     * the actions are told apart by the very expression the grammar matches
     * them with.
     *
     * @var non-empty-string
     */
    private const string PATTERN_TOKEN_ACTION = '(' . self::PATTERN_WORD . ')'
        . '\h*+\(\h*+(' . self::PATTERN_WORD . ')?\h*+\)';

    public static function createZeroOrOne(Context $context, mixed $children): Quantifier
    {
        $token = self::readTerminal($children);

        return new Quantifier(0, 1, $token->offset, self::calculateLength($token));
    }

    public static function createOneOrMore(Context $context, mixed $children): Quantifier
    {
        $token = self::readTerminal($children);

        return new Quantifier(1, \INF, $token->offset, self::calculateLength($token));
    }

    public static function createZeroOrMore(Context $context, mixed $children): Quantifier
    {
        $token = self::readTerminal($children);

        return new Quantifier(0, \INF, $token->offset, self::calculateLength($token));
    }

    public static function createRangeFromTo(Context $context, mixed $children): Quantifier
    {
        $from = self::readToken($children, 0);
        $to = self::readToken($children, 1);

        return new Quantifier(
            min: self::readNumber($from),
            max: self::readNumber($to),
            offset: $from->offset,
            length: self::calculateSpan($from->offset, $to->end),
        );
    }

    public static function createRangeFrom(Context $context, mixed $children): Quantifier
    {
        $from = self::readToken($children, 0);

        return new Quantifier(self::readNumber($from), \INF, $from->offset, self::calculateLength($from));
    }

    public static function createRangeTo(Context $context, mixed $children): Quantifier
    {
        $to = self::readToken($children, 0);

        return new Quantifier(0, self::readNumber($to), $to->offset, self::calculateLength($to));
    }

    public static function createRangeExactly(Context $context, mixed $children): Quantifier
    {
        $token = self::readTerminal($children);
        $count = self::readNumber($token);

        return new Quantifier($count, $count, $token->offset, self::calculateLength($token));
    }

    public static function createTokenDeclaration(Context $context, mixed $children): TokenDeclaration
    {
        return self::readTokenDeclaration($context, $children, false);
    }

    public static function createSkippedTokenDeclaration(Context $context, mixed $children): TokenDeclaration
    {
        return self::readTokenDeclaration($context, $children, true);
    }

    private static function readTokenDeclaration(
        Context $context,
        mixed $children,
        bool $isHidden,
    ): TokenDeclaration {
        $declaration = self::readTerminal($children);

        return new TokenDeclaration(
            name: self::readCapture($context, $declaration, 1),
            pattern: self::readCapture($context, $declaration, 2),
            state: self::findCapture($declaration, 0),
            isHidden: $isHidden,
            actions: self::readTokenActions($declaration),
            offset: $declaration->offset,
            length: self::calculateLength($declaration),
        );
    }

    /**
     * Returns everything the given declaration says the token does, in the
     * order it is written.
     *
     * The actions are captured as a single value, so they are told apart here,
     * and every one of them is pointed at by the place it is written at: the
     * arrow is what the actions of a declaration begin after, and nothing but
     * an action may be written past it.
     *
     * @return list<TokenAction>
     */
    private static function readTokenActions(Token $declaration): array
    {
        if (self::findCapture($declaration, 3) === null) {
            return [];
        }

        $position = \strrpos($declaration->value, self::ACTION_ARROW);

        // A declaration carrying an action is always written of an arrow, so
        // the declaration itself is only pointed at in theory
        $start = $position === false ? 0 : $position;

        \preg_match_all(
            pattern: '/' . self::PATTERN_TOKEN_ACTION . '/',
            subject: \substr($declaration->value, $start),
            matches: $matches,
            flags: \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE,
        );

        $result = [];

        foreach ($matches as $match) {
            [$action, $offset] = $match[0];

            $position = \max(0, $declaration->offset + $start + $offset);

            $result[] = new TokenAction(
                name: self::readWord($match[1][0]),
                argument: self::findWord($match[2][0] ?? ''),
                offset: $position,
                length: \strlen($action),
            );
        }

        return $result;
    }

    /**
     * @return non-empty-string
     */
    private static function readWord(string $value): string
    {
        \assert($value !== '', 'A name is written of at least one character');

        return $value;
    }

    /**
     * @return non-empty-string|null
     */
    private static function findWord(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    public static function createPragmaDeclaration(Context $context, mixed $children): PragmaDeclaration
    {
        $declaration = self::readTerminal($children);

        return new PragmaDeclaration(
            name: self::readCapture($context, $declaration, 0),
            value: self::readCapture($context, $declaration, 1),
            offset: $declaration->offset,
            length: self::calculateLength($declaration),
        );
    }

    public static function createIncludeDeclaration(Context $context, mixed $children): IncludeDeclaration
    {
        $declaration = self::readTerminal($children);

        return new IncludeDeclaration(
            target: self::readCapture($context, $declaration, 0),
            offset: $declaration->offset,
            length: self::calculateLength($declaration),
        );
    }

    public static function createLexerDeclaration(Context $context, mixed $children): LexerDeclaration
    {
        \assert(\is_array($children), 'A lexer declaration is a list of values');

        $name = self::readToken($children, 0);
        $lexer = $children[1] ?? null;

        \assert($name instanceof Token, 'A declaration is read as a single token');
        \assert($lexer instanceof CodeReducer, 'A lexer is declared as the code building it');

        return new LexerDeclaration(
            name: self::readCapture($context, $name, 0),
            lexer: $lexer,
            offset: $name->offset,
            length: self::calculateSpan($name->offset, $lexer->offset + $lexer->length),
        );
    }

    public static function createRuleDeclaration(Context $context, mixed $children): RuleDeclaration
    {
        \assert(\is_array($children), 'A rule declaration is a list of values');

        $name = self::readToken($children, 0);

        \array_shift($children);

        $body = \array_pop($children);
        $reducer = \array_pop($children);

        \assert($body instanceof Statement, 'A rule declaration ends with what it recognizes');
        \assert($reducer === null || $reducer instanceof Reducer);

        return new RuleDeclaration(
            name: self::readValue($name),
            body: $body,
            reducer: $reducer,
            offset: $name->offset,
            length: self::calculateSpan($name->offset, $body->offset + $body->length),
        );
    }

    public static function createCodeReducer(Context $context, mixed $children): CodeReducer
    {
        $token = self::readEmbedding($children);
        $read = $token->children;

        // The braces surrounding the code belong to the grammar rather than
        // to the code itself
        $open = $read[0] ?? null;
        $close = $read[\count($read) - 1] ?? null;

        $code = $open === null || $close === null
            ? ''
            : \substr($context->content, $open->end, $close->offset - $open->end);

        return new CodeReducer(self::dedent($code), $token->offset, self::calculateLength($token));
    }

    /**
     * Takes the body out of the nesting the grammar has written it in.
     *
     * A body is read from the grammar file exactly as it is written there, so
     * the nesting of the rule it belongs to is a part of it. The body is
     * written into something else afterwards — a generated method, an
     * evaluated callback — which nests it on its own, so a single level of
     * nesting is taken away.
     *
     * The line the body starts at is written after the brace opening it rather
     * than on a line of its own, so whatever precedes it is not nesting at all.
     */
    private static function dedent(string $code): string
    {
        $lines = \explode("\n", $code);

        foreach ($lines as $index => $line) {
            $lines[$index] = $index === 0
                ? \ltrim($line)
                : \preg_replace('/^ {1,' . self::NESTING_SIZE . '}/', '', $line);
        }

        return \trim(\implode("\n", $lines));
    }

    public static function createAlternation(Context $context, mixed $children): Statement
    {
        $statements = self::readStatements($children);

        if (!isset($statements[1])) {
            return $statements[0];
        }

        return new Alternation($statements, $statements[0]->offset, self::calculateStatementsLength($statements));
    }

    public static function createConcatenation(Context $context, mixed $children): Statement
    {
        $statements = self::readStatements($children);

        if (!isset($statements[1])) {
            return $statements[0];
        }

        return new Concatenation($statements, $statements[0]->offset, self::calculateStatementsLength($statements));
    }

    public static function createRepetition(Context $context, mixed $children): Statement
    {
        \assert(\is_array($children), 'A suffixed statement is a list of values');

        $statement = $children[0] ?? null;
        $quantifier = $children[1] ?? null;

        \assert($statement instanceof Statement);

        if (!$quantifier instanceof Quantifier) {
            return $statement;
        }

        return new Repetition($statement, $quantifier, $statement->offset, self::calculateSpan(
            $statement->offset,
            $quantifier->offset + $quantifier->length,
        ));
    }

    public static function createKeptTokenReference(Context $context, mixed $children): TokenReference
    {
        $token = self::readToken($children, 0);

        return new TokenReference(self::readValue($token), true, $token->offset, self::calculateLength($token));
    }

    public static function createSkippedTokenReference(Context $context, mixed $children): TokenReference
    {
        $token = self::readToken($children, 0);

        return new TokenReference(self::readValue($token), false, $token->offset, self::calculateLength($token));
    }

    public static function createRuleReference(Context $context, mixed $children): RuleReference
    {
        $token = self::readToken($children, 0);

        return new RuleReference(self::readValue($token), $token->offset, self::calculateLength($token));
    }

    public static function createInlinePattern(Context $context, mixed $children): InlinePattern
    {
        $token = self::readTerminal($children);

        // The quotes surrounding the pattern belong to the grammar rather than
        // to the pattern itself
        $pattern = \str_replace('\\"', '"', \substr($token->value, 1, -1));

        return new InlinePattern($pattern, $token->offset, self::calculateLength($token));
    }

    /**
     * Returns the number of bytes the given token has been read from.
     *
     * @return int<0, max>
     */
    private static function calculateLength(TokenInterface $token): int
    {
        return self::calculateSpan($token->offset, $token->end);
    }

    /**
     * Returns the number of bytes between the given positions.
     *
     * @param int<0, max> $from
     * @param int<0, max> $to
     * @return int<0, max>
     */
    private static function calculateSpan(int $from, int $to): int
    {
        return \max(0, $to - $from);
    }

    /**
     * Returns the number of bytes the statements of a production are written
     * of, from the beginning of the first one to the end of the last.
     *
     * @param non-empty-list<Statement> $statements
     * @return int<0, max>
     */
    private static function calculateStatementsLength(array $statements): int
    {
        $last = $statements[\count($statements) - 1];

        return self::calculateSpan($statements[0]->offset, $last->offset + $last->length);
    }

    /**
     * Returns the token recognized by a rule made of a single terminal.
     */
    private static function readTerminal(mixed $children): Token
    {
        \assert($children instanceof Token, 'A terminal recognizes a single token');

        return $children;
    }

    /**
     * Returns the token a lexer of its own has been read along with.
     */
    private static function readEmbedding(mixed $children): TokenEmbedding
    {
        \assert($children instanceof TokenEmbedding, 'The token carries what has been read');

        return $children;
    }

    /**
     * Returns what the subgroup at the given position has captured, or
     * {@see null} in case of the subgroup has captured nothing.
     *
     * @param int<0, max> $index
     * @return non-empty-string|null
     */
    private static function findCapture(Token $token, int $index): ?string
    {
        $value = $token->captures[$index] ?? '';

        return $value === '' ? null : $value;
    }

    /**
     * @param int<0, max> $index
     * @return non-empty-string
     * @throws UnexpectedTokenException in case of the declaration is incomplete
     */
    private static function readCapture(Context $context, Token $token, int $index): string
    {
        return self::findCapture($token, $index)
            ?? throw UnexpectedTokenException::fromToken($context->source, $token);
    }

    /**
     * Returns the token at the given position of what a rule has recognized.
     *
     * @param int<0, max> $index
     */
    private static function readToken(mixed $children, int $index): TokenInterface
    {
        \assert(\is_array($children), 'A production recognizes a list of values');

        $token = $children[$index] ?? null;

        \assert($token instanceof TokenInterface, 'The value is a token');

        return $token;
    }

    /**
     * @return non-empty-string
     */
    private static function readValue(TokenInterface $token): string
    {
        $value = $token->value;

        \assert($value !== '', 'The lexer never produces an empty token');

        return $value;
    }

    /**
     * @return int<0, max>
     */
    private static function readNumber(TokenInterface $token): int
    {
        $value = (int) $token->value;

        \assert($value >= 0, 'A number is written of digits only');

        return $value;
    }

    /**
     * @return non-empty-list<Statement>
     */
    private static function readStatements(mixed $children): array
    {
        \assert(\is_array($children), 'A production recognizes a list of values');

        $result = [];

        foreach ($children as $statement) {
            \assert($statement instanceof Statement, 'The value is a statement');

            $result[] = $statement;
        }

        \assert($result !== [], 'A production recognizes at least one statement');

        return $result;
    }
}
