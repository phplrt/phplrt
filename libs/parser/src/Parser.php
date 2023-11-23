<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceExceptionInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Lexer\Driver\DriverInterface;
use Phplrt\Parser\Context\TreeBuilder;
use Phplrt\Parser\Environment\Factory as EnvironmentFactory;
use Phplrt\Parser\Environment\SelectorInterface;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Parser\Exception\UnrecognizedTokenException;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\ProductionInterface;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Grammar\TerminalInterface;
use Phplrt\Source\File;
use Phplrt\Source\SourceFactory;

/**
 * A recurrence recursive descent parser implementation.
 *
 * Is a kind of top-down parser built from a set of mutually recursive methods
 * defined in:
 *  - Phplrt\Parser\Rule\ProductionInterface::reduce()
 *  - Phplrt\Parser\Rule\TerminalInterface::reduce()
 *
 * Where each such class implements one of the terminals or productions of the
 * grammar. Thus the structure of the resulting program closely mirrors that
 * of the grammar it recognizes.
 *
 * A "recurrence" means that instead of predicting, the parser simply tries to
 * apply all the alternative rules in order, until one of the attempts succeeds.
 *
 * Such a parser may require exponential work time, and does not always
 * guarantee completion, depending on the grammar.
 *
 * NOTE: Vulnerable to left recursion, like:
 *
 * <code>
 *      Digit = "0" | "1" | "2" | "3" | "4" | "5" | "6" | "7" | "8" | "9" ;
 *      Operator = "+" | "-" | "*" | "/" ;
 *      Number = Digit { Digit } ;
 *
 *      Expression = Number | Number Operator ;
 *      (*           ^^^^^^   ^^^^^^
 *          In this case, the grammar is incorrect and should be replaced by:
 *
 *          Expression = Number { Operator } ;
 *      *)
 * </code>
 */
final class Parser implements ParserInterface, ParserConfigsInterface
{
    use ParserConfigsTrait;

    /**
     * @var non-empty-string
     */
    private const ERROR_BUFFER_TYPE = 'Buffer class should implement %s interface';

    /**
     * The lexer instance.
     *
     * @psalm-readonly-allow-private-mutation
     */
    private LexerInterface $lexer;

    /**
     * The {@see SelectorInterface} is responsible for preparing
     * and analyzing the PHP environment for the parser to work.
     *
     * @psalm-readonly-allow-private-mutation
     */
    private SelectorInterface $env;

    /**
     * The {@see BuilderInterface} is responsible for building the Abstract
     * Syntax Tree.
     *
     * @psalm-readonly-allow-private-mutation
     */
    private BuilderInterface $builder;

    /**
     * Sources factory.
     *
     * @psalm-readonly-allow-private-mutation
     */
    private SourceFactoryInterface $sources;

    /**
     * The initial state (initial rule identifier) of the parser.
     *
     * @var array-key|null
     * @psalm-readonly-allow-private-mutation
     */
    private $initial;

    /**
     * Array of transition rules for the parser.
     *
     * @var array<array-key, RuleInterface>
     *
     * @readonly
     * @psalm-readonly-allow-private-mutation
     */
    private array $rules = [];

    /**
     * @var Context|null
     */
    private ?Context $context = null;

    /**
     * @param iterable<array-key, RuleInterface> $grammar An iterable of the
     *        transition rules for the parser.
     * @param array<ParserConfigsInterface::CONFIG_*, mixed> $options
     */
    public function __construct(
        LexerInterface $lexer,
        iterable $grammar = [],
        array $options = [],
        ?SourceFactoryInterface $sources = null
    ) {
        $this->lexer = $lexer;
        $this->env = new EnvironmentFactory();

        $this->rules = self::bootGrammar($grammar);
        $this->builder = self::bootBuilder($options);
        $this->initial = self::bootInitialRule($options, $this->rules);
        $this->sources = self::bootSourcesFactory($sources);

        $this->bootParserConfigsTrait($options);
    }

    private static function bootSourcesFactory(?SourceFactoryInterface $factory): SourceFactoryInterface
    {
        return $factory ?? new SourceFactory();
    }

    /**
     * @param array{
     *     builder?: BuilderInterface|iterable<int|non-empty-string, \Closure(Context,mixed):mixed>|null
     * } $options
     */
    private static function bootBuilder(array $options): BuilderInterface
    {
        /**
         * @var BuilderInterface|iterable<int|non-empty-string, \Closure(Context,mixed):mixed> $builder
         */
        $builder = $options[self::CONFIG_AST_BUILDER] ?? [];

        if ($builder instanceof BuilderInterface) {
            return $builder;
        }

        return new TreeBuilder($builder);
    }

    /**
     * @param iterable<array-key, RuleInterface> $grammar
     *
     * @return array<array-key, RuleInterface>
     */
    private static function bootGrammar(iterable $grammar): array
    {
        if ($grammar instanceof \Traversable) {
            return \iterator_to_array($grammar);
        }

        return $grammar;
    }

    /**
     * The method is responsible for initializing the initial
     * state of the grammar.
     *
     * @param array{
     *     initial?: array-key|null
     * } $options
     * @param array<array-key, RuleInterface> $grammar
     *
     * @return array-key
     */
    private static function bootInitialRule(array $options, array $grammar)
    {
        $initial = $options[self::CONFIG_INITIAL_RULE] ?? null;

        if ($initial !== null) {
            return $initial;
        }

        $result = \array_key_first($grammar);

        if ($result === false || $result === null) {
            return 0;
        }

        return $result;
    }

    /**
     * Sets an initial state (initial rule identifier) of the parser.
     *
     * @param array-key $initial
     *
     * @deprecated since phplrt 3.4 and will be removed in 4.0
     */
    public function startsAt($initial): self
    {
        $this->initial = $initial;

        return $this;
    }

    /**
     * Sets an abstract syntax tree builder.
     *
     * @deprecated since phplrt 3.4 and will be removed in 4.0
     */
    public function buildUsing(BuilderInterface $builder): self
    {
        $this->builder = $builder;

        return $this;
    }

    /**
     * @param array<non-empty-string, mixed> $options
     * @return iterable<array-key, object>
     *
     * @throws SourceExceptionInterface
     */
    public function parse($source, array $options = []): iterable
    {
        if ($this->rules === []) {
            return [];
        }

        $this->env->prepare();

        $source = $this->sources->create($source);

        try {
            $buffer = $this->createBufferFromSource($source);

            $this->context = new Context($buffer, $source, $this->initial, $options);

            return $this->parseOrFail($this->context);
        } finally {
            $this->env->rollback();
        }
    }

    private function createBufferFromTokens(iterable $stream): BufferInterface
    {
        \assert(
            \is_subclass_of($this->buffer, BufferInterface::class),
            \sprintf(self::ERROR_BUFFER_TYPE, BufferInterface::class)
        );

        $class = $this->buffer;

        return new $class($stream);
    }

    private function createBufferFromSource(ReadableInterface $source): BufferInterface
    {
        try {
            return $this->createBufferFromTokens(
                $this->lexer->lex(File::new($source)),
            );
        } catch (RuntimeExceptionInterface $e) {
            throw UnrecognizedTokenException::fromLexerException($e);
        }
    }

    /**
     * @throws ParserRuntimeException
     */
    private function parseOrFail(Context $context): iterable
    {
        $result = $this->next($context);

        if (\is_iterable($result)
            && ($this->allowTrailingTokens || $this->isEoi($context->buffer))
        ) {
            return $result;
        }

        $token = $context->lastOrdinalToken ?? $context->buffer->current();

        throw UnexpectedTokenException::fromToken(
            $context->getSource(),
            $token,
            null,
            $this->lookupExpectedTokens($context),
        );
    }

    /**
     * @return list<non-empty-string>
     */
    private function lookupExpectedTokens(Context $context): array
    {
        $rule = $context->rule ?? $this->rules[$this->initial];

        $tokens = [];

        foreach ($rule->getTerminals($this->rules) as $terminal) {
            if ($terminal instanceof Lexeme && \is_string($terminal->token)) {
                $tokens[$terminal->token] = $terminal->token;
            }

            if (\count($tokens) >= 3) {
                break;
            }
        }

        return \array_values($tokens);
    }

    /**
     * @return mixed
     */
    private function next(Context $context)
    {
        if ($this->step !== null) {
            return ($this->step)($context, function () use ($context) {
                return $this->runNextStep($context);
            });
        }

        return $this->runNextStep($context);
    }

    private function runNextStep(Context $context)
    {
        $context->rule = $this->rules[$context->state];
        $result = null;

        switch (true) {
            case $context->rule instanceof ProductionInterface:
                $result = $context->rule->reduce($context->buffer, function ($state) use ($context) {
                    // Keep current state
                    $beforeState = $context->state;
                    $beforeLastProcessedToken = $context->lastProcessedToken;

                    // Update state
                    $context->state = $state;
                    $context->lastProcessedToken = $context->buffer->current();

                    $result = $this->next($context);

                    // Rollback previous state
                    $context->state = $beforeState;
                    $context->lastProcessedToken = $beforeLastProcessedToken;

                    return $result;
                });

                break;

            case $context->rule instanceof TerminalInterface:
                $result = $context->rule->reduce($context->buffer);

                if ($result !== null) {
                    $context->buffer->next();

                    if ($context->buffer->current()->getOffset() > $context->lastOrdinalToken->getOffset()) {
                        $context->lastOrdinalToken = $context->buffer->current();
                    }

                    if (!$context->rule->isKeep()) {
                        return [];
                    }
                }

                break;
        }

        if ($result === null) {
            return null;
        }

        $result = $this->builder->build($context, $result) ?? $result;

        if ($result instanceof NodeInterface) {
            $context->node = $result;
        }

        return $result;
    }

    /**
     * Matches a token identifier that marks the end of the source.
     */
    private function isEoi(BufferInterface $buffer): bool
    {
        $current = $buffer->current();

        return $current->getName() === $this->eoi;
    }

    /**
     * Returns last execution context.
     *
     * Typically used in conjunction with the "tolerant" mode of the parser.
     *
     * ```php
     *  $parser = new Parser(..., [Parser::CONFIG_ALLOW_TRAILING_TOKENS => true]);
     *  $parser->parse('...');
     *
     *  $context = $parser->getLastExecutionContext();
     *  var_dump($context->buffer->current()); // Returns the token where the parser stopped
     * ```
     */
    public function getLastExecutionContext(): ?Context
    {
        return $this->context;
    }
}
