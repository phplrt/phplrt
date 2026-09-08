<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Syntax\Common;

use Phplrt\Compiler\Exception\CompilerRuntimeException;
use Phplrt\Compiler\Exception\EmptyPatternException;
use Phplrt\Compiler\Exception\UnsupportedAnnotationException;
use Phplrt\Compiler\Exception\UnsupportedTransitionException;
use Phplrt\Compiler\Loader\GrammarReference;
use Phplrt\Compiler\Loader\SyntaxLoaderInterface;
use Phplrt\Compiler\Node\Annotation;
use Phplrt\Compiler\Node\Declaration\Declaration;
use Phplrt\Compiler\Node\Declaration\IncludeDeclaration;
use Phplrt\Compiler\Node\Declaration\PragmaDeclaration;
use Phplrt\Compiler\Node\Declaration\RuleDeclaration;
use Phplrt\Compiler\Node\Declaration\TokenDeclaration;
use Phplrt\Compiler\Node\Reducer\ClassReducer;
use Phplrt\Compiler\Node\Reducer\CodeReducer;
use Phplrt\Compiler\Node\Statement\Alternation;
use Phplrt\Compiler\Node\Statement\Annotated;
use Phplrt\Compiler\Node\Statement\Concatenation;
use Phplrt\Compiler\Node\Statement\InlinePattern;
use Phplrt\Compiler\Node\Statement\InlineValue;
use Phplrt\Compiler\Node\Statement\Predicate;
use Phplrt\Compiler\Node\Statement\Repetition;
use Phplrt\Compiler\Node\Statement\RuleReference;
use Phplrt\Compiler\Node\Statement\Statement;
use Phplrt\Compiler\Node\Statement\TokenReference;
use Phplrt\Contracts\Parser\Exception\ParserExceptionInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Definition\ValueTokenDefinition;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleReference as RuleReferenceDefinition;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Exception\MessagePlaceholder;

/**
 * Reads a grammar file of the PP family into the lexer and the parser it
 * describes.
 *
 * Everything the formats of the family spell the same way is read here, and a
 * format is left with what it spells on its own: the parser reading it, what a
 * token does to the reading and the settings a grammar may carry.
 */
abstract class PPLoader implements SyntaxLoaderInterface
{
    /**
     * The name of the state a token declared without one belongs to.
     *
     * @var non-empty-string
     */
    protected const STATE_DEFAULT = 'default';

    /**
     * The setting naming the rule the analysis starts at.
     *
     * @var non-empty-string
     */
    protected const PRAGMA_ROOT = 'root';

    /**
     * The name of the annotation saying what a rule reports in case of it
     * cannot be recognized.
     *
     * @var non-empty-string
     */
    private const ANNOTATION_ERROR = 'error';

    /**
     * The variables a reducer may be written of, along with what each of them
     * stands for.
     *
     * A grammar is written about the language it recognizes rather than about
     * the parser recognizing it, so what the analysis has reached is written by
     * a name of its own instead of being walked to through the context.
     *
     * @var array<non-empty-string, non-empty-string>
     */
    private const REDUCER_VARIABLES = [
        '$source' => '$ctx->source',
        '$content' => '$ctx->source->content',
        '$begin' => '$ctx->begin',
        '$offset' => '$ctx->begin',
        '$length' => '$ctx->length',
        '$end' => '$ctx->begin + $ctx->length',
        '$rule' => '$ctx->rule',
    ];

    /**
     * The line telling the reducer apart from the variables it is given.
     *
     * @var non-empty-string
     */
    private const REDUCER_VARIABLES_NOTICE = '// The variables below are declared by the compiler';

    /**
     * Reading a grammar file needs a parser of its own, which is built once
     * and reused by every grammar this loader reads.
     */
    private ?ParserInterface $runtime = null;

    public function load(ReadableInterface $source, ParserBuilder $parser, LexerBuilder $lexer): iterable
    {
        foreach ($this->parse($source) as $declaration) {
            if ($declaration instanceof IncludeDeclaration) {
                yield new GrammarReference(
                    target: $declaration->target,
                    offset: $declaration->offset,
                    length: $declaration->length,
                );

                continue;
            }

            $this->loadDeclaration($declaration, $source, $parser, $lexer);
        }
    }

    /**
     * Returns the parser reading the grammar files of this format.
     *
     * The parser is generated from the grammar describing the format and is
     * committed along with it, so reading a grammar file costs nothing but the
     * reading itself.
     */
    abstract protected function createParser(): ParserInterface;

    /**
     * @return list<Declaration>
     * @throws ParserExceptionInterface in case of the parser reading the
     *         grammar cannot be built
     * @throws RuntimeExceptionInterface in case of the grammar cannot be
     *         recognized
     */
    private function parse(ReadableInterface $source): array
    {
        $declarations = ($this->runtime ??= $this->createParser())->parse($source);

        \assert(\is_iterable($declarations), 'A grammar file is read into a list of declarations');

        $result = [];

        foreach ($declarations as $declaration) {
            \assert($declaration instanceof Declaration, 'A grammar file is written of declarations');

            $result[] = $declaration;
        }

        return $result;
    }

    /**
     * @throws CompilerRuntimeException
     */
    protected function loadDeclaration(
        Declaration $declaration,
        ReadableInterface $source,
        ParserBuilder $parser,
        LexerBuilder $lexer,
    ): void {
        if ($declaration instanceof TokenDeclaration) {
            $this->loadToken($declaration, $source, $lexer);

            return;
        }

        if ($declaration instanceof PragmaDeclaration) {
            $this->loadPragma($declaration, $source, $parser, $lexer);

            return;
        }

        if ($declaration instanceof RuleDeclaration) {
            $this->loadRule($declaration, $source, $parser, $lexer);
        }
    }

    /**
     * @throws CompilerRuntimeException
     */
    protected function loadToken(
        TokenDeclaration $declaration,
        ReadableInterface $source,
        LexerBuilder $lexer,
    ): void {
        $state = $declaration->state ?? static::STATE_DEFAULT;

        // A state of its own is read by a lexer of its own
        $target = $state === static::STATE_DEFAULT ? $lexer : $lexer->addLexer($state);

        $definition = $target->addPattern($declaration->pattern, $declaration->name);
        $definition->setHidden($declaration->isHidden);
        $definition->setSource($source, $declaration->offset, $declaration->length);

        $this->loadAction($definition, $declaration, $state, $source);
    }

    /**
     * Reads what the declaration says the token does to the reading.
     *
     * A format spells this out in a way of its own, so what a declaration
     * means is decided here rather than by the parser that has read it.
     *
     * @param non-empty-string $state the state the token belongs to
     * @throws CompilerRuntimeException
     */
    abstract protected function loadAction(
        TokenDefinition $definition,
        TokenDeclaration $declaration,
        string $state,
        ReadableInterface $source,
    ): void;

    /**
     * Translates the state a token switches to into what it does to the
     * reading.
     *
     * Entering a named state from the initial one is handing the reading over
     * to the lexer of that state, and switching back is giving the control
     * back. A grammar switching between two named states describes a reading
     * that is not nested, which cannot be expressed at all.
     *
     * @param non-empty-string $state
     * @throws UnsupportedTransitionException
     */
    final protected function loadTransition(
        TokenDefinition $definition,
        TokenDeclaration $declaration,
        string $state,
        ReadableInterface $source,
    ): void {
        $next = $declaration->next;

        if ($next === null || $next === $state) {
            return;
        }

        if ($state === static::STATE_DEFAULT) {
            $definition->enter($next);

            return;
        }

        if ($next === static::STATE_DEFAULT) {
            $definition->exit();

            return;
        }

        throw UnsupportedTransitionException::becauseTransitionIsNotSupported(
            source: $source,
            declaration: $declaration,
            from: $state,
            to: $next,
        );
    }

    /**
     * Reads the setting the declaration carries.
     *
     * Which settings a grammar may carry is a matter of the format, so what a
     * declaration means is decided by the loader of that format.
     *
     * @throws CompilerRuntimeException
     */
    abstract protected function loadPragma(
        PragmaDeclaration $declaration,
        ReadableInterface $source,
        ParserBuilder $parser,
        LexerBuilder $lexer,
    ): void;

    /**
     * Marks the rule the analysis starts at.
     */
    final protected function loadRootPragma(
        PragmaDeclaration $declaration,
        ReadableInterface $source,
        ParserBuilder $parser,
    ): void {
        /**
         * The rule the grammar starts at may well be declared in a grammar
         * that has not been read yet, so it is pointed at by name.
         */
        $reference = $parser->addRuleReference($declaration->value);
        $reference->setSource($source, $declaration->offset, $declaration->length);

        $parser->setInitialRule($reference);
    }

    /**
     * @throws CompilerRuntimeException
     */
    private function loadRule(
        RuleDeclaration $declaration,
        ReadableInterface $source,
        ParserBuilder $parser,
        LexerBuilder $lexer,
    ): void {
        $body = $this->loadStatement($declaration->body, $source, $parser, $lexer);

        $rule = $this->nameRule($body, $declaration->name, $parser);
        $rule->setSource($source, $declaration->offset, $declaration->length);

        $this->loadReducer($rule, $declaration, $source);
        $this->loadAnnotations($rule, $declaration->annotations, $source);

        /**
         * The rule declared first is where the analysis starts, unless the
         * grammar names another one.
         */
        if ($parser->initial === null) {
            $parser->setInitialRule($rule);
        }
    }

    /**
     * Returns the rule the declaration is known by.
     *
     * A reference stands for another rule instead of being one and never
     * reaches the compiled grammar, so a rule written of nothing but a
     * reference is wrapped into a production to be named at all.
     *
     * @param non-empty-string $name
     */
    private function nameRule(RuleDefinition $body, string $name, ParserBuilder $parser): RuleDefinition
    {
        if ($body instanceof RuleReferenceDefinition) {
            return $parser->addConcatenation([$body], $name);
        }

        $body->setName($name);

        return $body;
    }

    private function loadReducer(
        RuleDefinition $rule,
        RuleDeclaration $declaration,
        ReadableInterface $source,
    ): void {
        $code = $this->createReducerCode($declaration);

        if ($code === '') {
            return;
        }

        $reducer = $declaration->reducer;

        $compiled = new PhpCodeReducer(self::createReducerVariables($code) . $code);
        $compiled->setSource(
            source: $source,
            offset: $reducer === null ? $declaration->offset : $reducer->offset,
            length: $reducer === null ? $declaration->length : $reducer->length,
        );

        $rule->setReducer($compiled);
    }

    /**
     * Returns the body of the reducer building the node of the given rule, or
     * an empty string in case of the rule is reduced to its children.
     */
    private function createReducerCode(RuleDeclaration $declaration): string
    {
        $reducer = $declaration->reducer;

        if ($reducer instanceof ClassReducer) {
            return \sprintf('return new \\%s($ctx, $children);', \ltrim($reducer->class, '\\'));
        }

        // An empty body ("-> {}") is written the same way a missing reducer
        // is meant
        if ($reducer instanceof CodeReducer && $reducer->code !== '') {
            return $reducer->code;
        }

        return '';
    }

    /**
     * Returns the declarations of the variables the given body is written of,
     * or an empty string in case of the body is written of none of them.
     */
    private static function createReducerVariables(string $code): string
    {
        $used = self::findVariables($code);
        $declarations = [];

        foreach (self::REDUCER_VARIABLES as $variable => $expression) {
            if (isset($used[$variable])) {
                $declarations[] = \sprintf('%s = %s;', $variable, $expression);
            }
        }

        if ($declarations === []) {
            return '';
        }

        return self::REDUCER_VARIABLES_NOTICE . "\n"
            . \implode("\n", $declarations) . "\n\n";
    }

    /**
     * Returns the variables the given code is written of.
     *
     * The code is read the way PHP reads it, so a variable written inside a
     * string or a comment is not mistaken for the variable itself.
     *
     * @return array<non-empty-string, true>
     */
    private static function findVariables(string $code): array
    {
        $variables = [];

        foreach (\token_get_all('<?php ' . $code) as $token) {
            if (\is_array($token) && $token[0] === \T_VARIABLE && $token[1] !== '') {
                $variables[$token[1]] = true;
            }
        }

        return $variables;
    }

    /**
     * @throws CompilerRuntimeException
     */
    private function loadStatement(
        Statement $statement,
        ReadableInterface $source,
        ParserBuilder $parser,
        LexerBuilder $lexer,
    ): RuleDefinition {
        $rule = $this->createStatement($statement, $source, $parser, $lexer);

        $rule->setSource($source, $statement->offset, $statement->length);

        return $rule;
    }

    /**
     * @throws CompilerRuntimeException
     */
    private function createStatement(
        Statement $statement,
        ReadableInterface $source,
        ParserBuilder $parser,
        LexerBuilder $lexer,
    ): RuleDefinition {
        return match (true) {
            $statement instanceof Alternation => $parser->addAlternation(
                $this->loadStatements($statement->statements, $source, $parser, $lexer),
            ),
            $statement instanceof Concatenation => $parser->addConcatenation(
                $this->loadStatements($statement->statements, $source, $parser, $lexer),
            ),
            $statement instanceof Repetition => $this->createRepetition($statement, $source, $parser, $lexer),
            $statement instanceof Predicate => $parser->addPredicate(
                rule: $this->loadStatement($statement->statement, $source, $parser, $lexer),
                isExpected: $statement->isExpected,
            ),
            $statement instanceof Annotated => $this->createAnnotated($statement, $source, $parser, $lexer),
            $statement instanceof RuleReference => $parser->addRuleReference($statement->name),
            $statement instanceof TokenReference => $parser->addTokenReference($statement->name)
                ->setKept($statement->isKept),
            $statement instanceof InlinePattern => $this->createInlinePattern($statement, $source, $parser, $lexer),
            $statement instanceof InlineValue => $this->createInlineValue($statement, $source, $parser, $lexer),
        };
    }

    /**
     * @param non-empty-list<Statement> $statements
     * @return list<RuleDefinition>
     * @throws CompilerRuntimeException
     */
    private function loadStatements(
        array $statements,
        ReadableInterface $source,
        ParserBuilder $parser,
        LexerBuilder $lexer,
    ): array {
        $result = [];

        foreach ($statements as $statement) {
            $result[] = $this->loadStatement($statement, $source, $parser, $lexer);
        }

        return $result;
    }

    /**
     * Reads a statement along with what is said about it.
     *
     * @throws CompilerRuntimeException
     */
    private function createAnnotated(
        Annotated $statement,
        ReadableInterface $source,
        ParserBuilder $parser,
        LexerBuilder $lexer,
    ): RuleDefinition {
        $rule = $this->loadStatement($statement->statement, $source, $parser, $lexer);

        /**
         * A reference stands for another rule instead of being one and never
         * reaches the compiled grammar, so what is said about it is said about
         * a production wrapping it.
         */
        if ($rule instanceof RuleReferenceDefinition) {
            $rule = $parser->addConcatenation([$rule]);
            $rule->setSource($source, $statement->offset, $statement->length);
        }

        $this->loadAnnotations($rule, $statement->annotations, $source);

        return $rule;
    }

    /**
     * Reads what is said about a rule apart from what it recognizes.
     *
     * @param list<Annotation> $annotations
     * @throws UnsupportedAnnotationException
     */
    private function loadAnnotations(RuleDefinition $rule, array $annotations, ReadableInterface $source): void
    {
        foreach ($annotations as $annotation) {
            match ($annotation->name) {
                self::ANNOTATION_ERROR => $this->loadErrorAnnotation($rule, $annotation, $source),
                default => throw UnsupportedAnnotationException::becauseAnnotationIsNotSupported(
                    source: $source,
                    annotation: $annotation,
                ),
            };
        }
    }

    /**
     * Reads the message the rule reports in case of it cannot be recognized.
     *
     * @throws UnsupportedAnnotationException
     */
    private function loadErrorAnnotation(
        RuleDefinition $rule,
        Annotation $annotation,
        ReadableInterface $source,
    ): void {
        // A rule reports a single error, so the one it already reports is the
        // one the second message would be lost behind
        if ($rule->message !== null) {
            throw UnsupportedAnnotationException::becauseAnnotationIsWrittenTwice($source, $annotation);
        }

        if (\count($annotation->arguments) !== 1) {
            throw UnsupportedAnnotationException::becauseAnnotationExpectsValues($source, $annotation, 1);
        }

        $message = $annotation->arguments[0];

        if ($message === '') {
            throw UnsupportedAnnotationException::becauseAnnotationExpectsNonEmptyValue($source, $annotation);
        }

        self::assertPlaceholdersAreDefined($message, $annotation, $source);

        $rule->setMessage($message);
    }

    /**
     * Reports a message asking about something the reading knows no value for.
     *
     * @throws UnsupportedAnnotationException
     */
    private static function assertPlaceholdersAreDefined(
        string $message,
        Annotation $annotation,
        ReadableInterface $source,
    ): void {
        \preg_match_all(MessagePlaceholder::PATTERN, $message, $matches, \PREG_SET_ORDER);

        foreach ($matches as $match) {
            $name = $match[1] ?? '';

            // A pair of braces stands for a brace of the message itself and
            // asks about nothing
            if ($name === '' || MessagePlaceholder::tryFrom($name) !== null) {
                continue;
            }

            throw UnsupportedAnnotationException::becausePlaceholderIsNotSupported(
                source: $source,
                annotation: $annotation,
                placeholder: $name,
            );
        }
    }

    /**
     * @throws CompilerRuntimeException
     */
    private function createRepetition(
        Repetition $statement,
        ReadableInterface $source,
        ParserBuilder $parser,
        LexerBuilder $lexer,
    ): RuleDefinition {
        $rule = $this->loadStatement($statement->statement, $source, $parser, $lexer);
        $quantifier = $statement->quantifier;

        if ($quantifier->min === 0 && $quantifier->max === 1) {
            return $parser->addOptional($rule);
        }

        return $parser->addRepetition($rule, $quantifier->max, $quantifier->min);
    }

    /**
     * @throws EmptyPatternException
     */
    private function createInlinePattern(
        InlinePattern $statement,
        ReadableInterface $source,
        ParserBuilder $parser,
        LexerBuilder $lexer,
    ): RuleDefinition {
        $pattern = $statement->pattern;

        if ($pattern === '') {
            throw EmptyPatternException::becausePatternIsEmpty($source, $statement);
        }

        $token = $this->findInlineToken($lexer, $pattern);

        if ($token === null) {
            $token = $lexer->addPattern($pattern);
            $token->setSource($source, $statement->offset, $statement->length);
        }

        /**
         * Such a token stands for the punctuation a rule reads but says
         * nothing about, so it is never kept in the tree.
         */
        return $parser->addTokenReference($token)
            ->skip();
    }

    /**
     * Reads the token a rule declares by the text it recognizes.
     *
     * @throws CompilerRuntimeException
     */
    private function createInlineValue(
        InlineValue $statement,
        ReadableInterface $source,
        ParserBuilder $parser,
        LexerBuilder $lexer,
    ): RuleDefinition {
        $value = $statement->value;

        if ($value === '') {
            throw EmptyPatternException::becausePatternIsEmpty($source, $statement);
        }

        $token = $this->findInlineValue($lexer, $value);

        if ($token === null) {
            $token = $lexer->addValue($value);
            $token->setSource($source, $statement->offset, $statement->length);
        }

        /**
         * Such a token stands for the punctuation a rule reads but says
         * nothing about, so it is never kept in the tree.
         */
        return $parser->addTokenReference($token)
            ->skip();
    }

    /**
     * Returns the token another rule has already declared by the very same
     * text, so that the same punctuation is read by a single token.
     */
    private function findInlineValue(LexerBuilder $lexer, string $value): ?ValueTokenDefinition
    {
        foreach ($lexer->tokens as $token) {
            if ($token instanceof ValueTokenDefinition
                && $token->name === null
                && $token->value === $value
            ) {
                return $token;
            }
        }

        return null;
    }

    /**
     * Returns the token the very same pattern has already been declared as, so
     * that a pattern written in several rules is read by a single token.
     *
     * @param non-empty-string $pattern
     */
    private function findInlineToken(LexerBuilder $lexer, string $pattern): ?RegexTokenDefinition
    {
        foreach ($lexer->tokens as $token) {
            if ($token instanceof RegexTokenDefinition
                && $token->name === null
                && $token->regex === $pattern
            ) {
                return $token;
            }
        }

        return null;
    }
}
