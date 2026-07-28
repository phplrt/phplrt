<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Syntax\PP2;

use Phplrt\Compiler\Exception\CompilerRuntimeException;
use Phplrt\Compiler\Exception\EmptyPatternException;
use Phplrt\Compiler\Exception\UnsupportedPragmaException;
use Phplrt\Compiler\Exception\UnsupportedTransitionException;
use Phplrt\Compiler\Loader\GrammarReference;
use Phplrt\Compiler\Loader\SyntaxLoaderInterface;
use Phplrt\Compiler\Node\Declaration\Declaration;
use Phplrt\Compiler\Node\Declaration\IncludeDeclaration;
use Phplrt\Compiler\Node\Declaration\PragmaDeclaration;
use Phplrt\Compiler\Node\Declaration\RuleDeclaration;
use Phplrt\Compiler\Node\Declaration\TokenDeclaration;
use Phplrt\Compiler\Node\Reducer\ClassReducer;
use Phplrt\Compiler\Node\Reducer\CodeReducer;
use Phplrt\Compiler\Node\Statement\Alternation;
use Phplrt\Compiler\Node\Statement\Concatenation;
use Phplrt\Compiler\Node\Statement\InlinePattern;
use Phplrt\Compiler\Node\Statement\Repetition;
use Phplrt\Compiler\Node\Statement\RuleReference;
use Phplrt\Compiler\Node\Statement\Statement;
use Phplrt\Compiler\Node\Statement\TokenReference;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleReference as RuleReferenceDefinition;
use Phplrt\Parser\Builder\ParserBuilder;

/**
 * Reads a PP2 grammar file into the lexer and the parser it describes.
 */
class PP2Loader implements SyntaxLoaderInterface
{
    /**
     * The name of the state a token declared without one belongs to.
     *
     * @var non-empty-string
     */
    protected const string STATE_DEFAULT = 'default';

    /**
     * The setting naming the rule the analysis starts at.
     *
     * @var non-empty-string
     */
    protected const string PRAGMA_ROOT = 'root';

    /**
     * The body of the reducer standing for the "#" marker, which builds no
     * node of its own.
     *
     * @var non-empty-string
     */
    private const string REDUCER_KEEP = 'return $children;';

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
    private const array REDUCER_VARIABLES = [
        '$source' => '$ctx->source',
        '$content' => '$ctx->content',
        '$token' => '$ctx->token',
        '$offset' => '$ctx->token->offset',
        '$rule' => '$ctx->rule',
    ];

    /**
     * The line telling the reducer apart from the variables it is given.
     *
     * @var non-empty-string
     */
    private const string REDUCER_VARIABLES_NOTICE = '// The variables below are declared by the compiler';

    /**
     * Reading a grammar file needs a parser of its own, which is built once
     * and reused by every grammar this loader reads.
     *
     * @var ParserInterface<array<Declaration>>|null
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
     * Describes the parser reading the grammar files of this format.
     *
     * @return ParserInterface<array<Declaration>>
     */
    protected function createParser(): ParserInterface
    {
        return new PP2Parser();
    }

    /**
     * @return array<Declaration>
     */
    private function parse(ReadableInterface $source): array
    {
        return ($this->runtime ??= $this->createParser())->parse($source);
    }

    /**
     * @throws CompilerRuntimeException
     */
    private function loadDeclaration(
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
            $this->loadPragma($declaration, $source, $parser);

            return;
        }

        if ($declaration instanceof RuleDeclaration) {
            $this->loadRule($declaration, $source, $parser, $lexer);
        }
    }

    /**
     * @throws CompilerRuntimeException
     */
    private function loadToken(
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

        $this->loadTransition($definition, $declaration, $state, $source);
    }

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
    private function loadTransition(
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
     * @throws UnsupportedPragmaException
     */
    private function loadPragma(
        PragmaDeclaration $declaration,
        ReadableInterface $source,
        ParserBuilder $parser,
    ): void {
        if ($declaration->name !== static::PRAGMA_ROOT) {
            throw UnsupportedPragmaException::becausePragmaIsNotSupported($source, $declaration);
        }

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

        /**
         * A rule declared with the "#" prefix is kept in the tree even when it
         * recognizes a single child, and a rule building a node of its own is
         * exactly what is kept, so the marker is honoured by a reducer handing
         * the children over as they are.
         */
        return $declaration->isKept ? self::REDUCER_KEEP : '';
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
            $statement instanceof RuleReference => $parser->addRuleReference($statement->name),
            $statement instanceof TokenReference => $parser->addTokenReference($statement->name)
                ->setKept($statement->isKept),
            $statement instanceof InlinePattern => $this->createInlinePattern($statement, $source, $parser, $lexer),
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
