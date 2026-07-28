<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Syntax\PP2;

use Phplrt\Compiler\Node\Declaration\IncludeDeclaration;
use Phplrt\Compiler\Node\Declaration\PragmaDeclaration;
use Phplrt\Compiler\Node\Declaration\RuleDeclaration;
use Phplrt\Compiler\Node\Declaration\TokenDeclaration;
use Phplrt\Compiler\Node\Reducer\ClassReducer;
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
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Context;
use Phplrt\Parser\Exception\UnexpectedTokenException;

/**
 * Reads a PP2 grammar file into the declarations it is written of.
 */
final readonly class PP2ParserBuilder
{
    public static function create(): ParserBuilder
    {
        $parser = new ParserBuilder();

        self::addDeclarationRules($parser);
        self::addStatementRules($parser);
        self::addQuantifierRules($parser);

        return $parser;
    }

    /**
     * A grammar file is a list of declarations, each of them written either as
     * a "%" directive or as a rule of the parser.
     */
    private static function addDeclarationRules(ParserBuilder $parser): void
    {
        $parser->setInitialRule($parser->addRepetition(
            rule: $parser->addRuleReference('Declaration'),
            name: 'Grammar',
        ));

        $parser->addAlternation([
            $parser->addRuleReference('TokenDeclaration'),
            $parser->addRuleReference('SkippedTokenDeclaration'),
            $parser->addRuleReference('PragmaDeclaration'),
            $parser->addRuleReference('IncludeDeclaration'),
            $parser->addRuleReference('RuleDeclaration'),
        ], 'Declaration');

        // %token string:T_QUOTE " -> default
        $parser->addTokenReference('T_TOKEN', 'TokenDeclaration')
            ->setReducer(self::createTokenDeclaration(...));

        // %skip T_WHITESPACE \s++
        $parser->addTokenReference('T_SKIP', 'SkippedTokenDeclaration')
            ->setReducer(self::createSkippedTokenDeclaration(...));

        // %pragma root Expression
        $parser->addTokenReference('T_PRAGMA', 'PragmaDeclaration')
            ->setReducer(self::createPragmaDeclaration(...));

        // %include grammar/lexemes
        $parser->addTokenReference('T_INCLUDE', 'IncludeDeclaration')
            ->setReducer(self::createIncludeDeclaration(...));

        // #Sum -> { return new SumNode($children); } : Number() ;
        $parser->addConcatenation([
            $parser->addOptional($parser->addRuleReference('KeptMarker')),
            $parser->addTokenReference('T_NAME'),
            $parser->addOptional($parser->addRuleReference('Reducer')),
            $parser->addTokenReference('T_EQ')->skip(),
            $parser->addRuleReference('Alternation'),
            $parser->addOptional($parser->addTokenReference('T_SEMICOLON')->skip()),
        ], 'RuleDeclaration')
            ->setReducer(self::createRuleDeclaration(...));

        $parser->addTokenReference('T_HASH', 'KeptMarker')
            ->setReducer(static fn(): bool => true);

        $parser->addAlternation([
            $parser->addRuleReference('CodeReducer'),
            $parser->addRuleReference('ClassReducer'),
        ], 'Reducer');

        $parser->addTokenReference('T_PHP', 'CodeReducer')
            ->setReducer(self::createCodeReducer(...));

        $parser->addConcatenation([
            $parser->addTokenReference('T_ARROW')->skip(),
            $parser->addTokenReference('T_NAME'),
        ], 'ClassReducer')
            ->setReducer(self::createClassReducer(...));
    }

    /**
     * What a rule of the parser recognizes: the alternatives it is written of,
     * each of them a sequence of statements.
     */
    private static function addStatementRules(ParserBuilder $parser): void
    {
        // Number() | Name()
        $parser->addConcatenation([
            $parser->addRuleReference('Concatenation'),
            $parser->addRepetition($parser->addRuleReference('AlternationTail')),
        ], 'Alternation')
            ->setReducer(self::createAlternation(...));

        $parser->addConcatenation([
            $parser->addTokenReference('T_OR')->skip(),
            $parser->addRuleReference('Concatenation'),
        ], 'AlternationTail');

        // Number() ::T_PLUS:: Number()
        $parser->addRepetition(
            rule: $parser->addRuleReference('Suffixed'),
            min: 1,
            name: 'Concatenation',
        )
            ->setReducer(self::createConcatenation(...));

        // Number()*
        $parser->addConcatenation([
            $parser->addRuleReference('Primary'),
            $parser->addOptional($parser->addRuleReference('Quantifier')),
        ], 'Suffixed')
            ->setReducer(self::createRepetition(...));

        $parser->addAlternation([
            $parser->addRuleReference('Group'),
            $parser->addRuleReference('KeptTokenReference'),
            $parser->addRuleReference('SkippedTokenReference'),
            $parser->addRuleReference('RuleReference'),
            $parser->addRuleReference('InlinePattern'),
        ], 'Primary');

        // ( Number() | Name() )
        $parser->addConcatenation([
            $parser->addTokenReference('T_PARENTHESIS_OPEN')->skip(),
            $parser->addRuleReference('Alternation'),
            $parser->addTokenReference('T_PARENTHESIS_CLOSE')->skip(),
        ], 'Group');

        // <T_NAME>
        $parser->addConcatenation([
            $parser->addTokenReference('T_ANGLE_OPEN')->skip(),
            $parser->addTokenReference('T_NAME'),
            $parser->addTokenReference('T_ANGLE_CLOSE')->skip(),
        ], 'KeptTokenReference')
            ->setReducer(self::createKeptTokenReference(...));

        // ::T_NAME::
        $parser->addConcatenation([
            $parser->addTokenReference('T_DOUBLE_COLON')->skip(),
            $parser->addTokenReference('T_NAME'),
            $parser->addTokenReference('T_DOUBLE_COLON')->skip(),
        ], 'SkippedTokenReference')
            ->setReducer(self::createSkippedTokenReference(...));

        // Number()
        $parser->addConcatenation([
            $parser->addTokenReference('T_NAME'),
            $parser->addTokenReference('T_PARENTHESIS_OPEN')->skip(),
            $parser->addTokenReference('T_PARENTHESIS_CLOSE')->skip(),
        ], 'RuleReference')
            ->setReducer(self::createRuleReference(...));

        // "\+"
        $parser->addTokenReference('T_STRING', 'InlinePattern')
            ->setReducer(self::createInlinePattern(...));
    }

    /**
     * How many times a statement may repeat.
     */
    private static function addQuantifierRules(ParserBuilder $parser): void
    {
        $parser->addAlternation([
            $parser->addRuleReference('ZeroOrOne'),
            $parser->addRuleReference('OneOrMore'),
            $parser->addRuleReference('ZeroOrMore'),
            $parser->addRuleReference('Range'),
        ], 'Quantifier');

        // ?
        $parser->addTokenReference('T_QUESTION_MARK', 'ZeroOrOne')
            ->setReducer(self::createZeroOrOne(...));

        // +
        $parser->addTokenReference('T_PLUS', 'OneOrMore')
            ->setReducer(self::createOneOrMore(...));

        // *
        $parser->addTokenReference('T_ASTERISK', 'ZeroOrMore')
            ->setReducer(self::createZeroOrMore(...));

        // {2,5}
        $parser->addConcatenation([
            $parser->addTokenReference('T_BRACE_OPEN')->skip(),
            $parser->addRuleReference('RangeBody'),
            $parser->addTokenReference('T_BRACE_CLOSE')->skip(),
        ], 'Range');

        $parser->addAlternation([
            $parser->addRuleReference('RangeFromTo'),
            $parser->addRuleReference('RangeFrom'),
            $parser->addRuleReference('RangeTo'),
            $parser->addRuleReference('RangeExactly'),
        ], 'RangeBody');

        // {2,5}
        $parser->addConcatenation([
            $parser->addTokenReference('T_INT'),
            $parser->addTokenReference('T_COMMA')->skip(),
            $parser->addTokenReference('T_INT'),
        ], 'RangeFromTo')
            ->setReducer(self::createRangeFromTo(...));

        // {2,}
        $parser->addConcatenation([
            $parser->addTokenReference('T_INT'),
            $parser->addTokenReference('T_COMMA')->skip(),
        ], 'RangeFrom')
            ->setReducer(self::createRangeFrom(...));

        // {,5}
        $parser->addConcatenation([
            $parser->addTokenReference('T_COMMA')->skip(),
            $parser->addTokenReference('T_INT'),
        ], 'RangeTo')
            ->setReducer(self::createRangeTo(...));

        // {5}
        $parser->addTokenReference('T_INT', 'RangeExactly')
            ->setReducer(self::createRangeExactly(...));
    }

    private static function createZeroOrOne(Context $context, mixed $children): Quantifier
    {
        $token = self::readTerminal($children);

        return new Quantifier(0, 1, $token->offset, self::calculateLength($token));
    }

    private static function createOneOrMore(Context $context, mixed $children): Quantifier
    {
        $token = self::readTerminal($children);

        return new Quantifier(1, \INF, $token->offset, self::calculateLength($token));
    }

    private static function createZeroOrMore(Context $context, mixed $children): Quantifier
    {
        $token = self::readTerminal($children);

        return new Quantifier(0, \INF, $token->offset, self::calculateLength($token));
    }

    private static function createRangeFromTo(Context $context, mixed $children): Quantifier
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

    private static function createRangeFrom(Context $context, mixed $children): Quantifier
    {
        $from = self::readToken($children, 0);

        return new Quantifier(self::readNumber($from), \INF, $from->offset, self::calculateLength($from));
    }

    private static function createRangeTo(Context $context, mixed $children): Quantifier
    {
        $to = self::readToken($children, 0);

        return new Quantifier(0, self::readNumber($to), $to->offset, self::calculateLength($to));
    }

    private static function createRangeExactly(Context $context, mixed $children): Quantifier
    {
        $token = self::readTerminal($children);
        $count = self::readNumber($token);

        return new Quantifier($count, $count, $token->offset, self::calculateLength($token));
    }

    private static function createTokenDeclaration(Context $context, mixed $children): TokenDeclaration
    {
        return self::readTokenDeclaration($context, $children, false);
    }

    private static function createSkippedTokenDeclaration(Context $context, mixed $children): TokenDeclaration
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
            next: self::findCapture($declaration, 3),
            isHidden: $isHidden,
            offset: $declaration->offset,
            length: self::calculateLength($declaration),
        );
    }

    private static function createPragmaDeclaration(Context $context, mixed $children): PragmaDeclaration
    {
        $declaration = self::readTerminal($children);

        return new PragmaDeclaration(
            name: self::readCapture($context, $declaration, 0),
            value: self::readCapture($context, $declaration, 1),
            offset: $declaration->offset,
            length: self::calculateLength($declaration),
        );
    }

    private static function createIncludeDeclaration(Context $context, mixed $children): IncludeDeclaration
    {
        $declaration = self::readTerminal($children);

        return new IncludeDeclaration(
            target: self::readCapture($context, $declaration, 0),
            offset: $declaration->offset,
            length: self::calculateLength($declaration),
        );
    }

    private static function createRuleDeclaration(Context $context, mixed $children): RuleDeclaration
    {
        \assert(\is_array($children), 'A rule declaration is a list of values');

        $isKept = ($children[0] ?? null) === true;

        if ($isKept) {
            \array_shift($children);
        }

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
            isKept: $isKept,
            offset: $name->offset,
            length: self::calculateSpan($name->offset, $body->offset + $body->length),
        );
    }

    private static function createCodeReducer(Context $context, mixed $children): CodeReducer
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

        return new CodeReducer(\trim($code), $token->offset, self::calculateLength($token));
    }

    private static function createClassReducer(Context $context, mixed $children): ClassReducer
    {
        $token = self::readToken($children, 0);

        return new ClassReducer(self::readValue($token), $token->offset, self::calculateLength($token));
    }

    private static function createAlternation(Context $context, mixed $children): Statement
    {
        $statements = self::readStatements($children);

        if (!isset($statements[1])) {
            return $statements[0];
        }

        return new Alternation($statements, $statements[0]->offset, self::calculateStatementsLength($statements));
    }

    private static function createConcatenation(Context $context, mixed $children): Statement
    {
        $statements = self::readStatements($children);

        if (!isset($statements[1])) {
            return $statements[0];
        }

        return new Concatenation($statements, $statements[0]->offset, self::calculateStatementsLength($statements));
    }

    private static function createRepetition(Context $context, mixed $children): Statement
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

    private static function createKeptTokenReference(Context $context, mixed $children): TokenReference
    {
        $token = self::readToken($children, 0);

        return new TokenReference(self::readValue($token), true, $token->offset, self::calculateLength($token));
    }

    private static function createSkippedTokenReference(Context $context, mixed $children): TokenReference
    {
        $token = self::readToken($children, 0);

        return new TokenReference(self::readValue($token), false, $token->offset, self::calculateLength($token));
    }

    private static function createRuleReference(Context $context, mixed $children): RuleReference
    {
        $token = self::readToken($children, 0);

        return new RuleReference(self::readValue($token), $token->offset, self::calculateLength($token));
    }

    private static function createInlinePattern(Context $context, mixed $children): InlinePattern
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
