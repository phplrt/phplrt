<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Buffer\MutableBuffer;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Driver\DriverInterface;
use Phplrt\Lexer\Token\EndOfInput;
use Phplrt\Lexer\Token\Token;
use Phplrt\Parser\Environment\Factory as EnvironmentFactory;
use Phplrt\Parser\Environment\SelectorInterface;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Parser\Exception\UnexpectedTokenWithHintsException;
use Phplrt\Parser\Exception\UnrecognizedTokenException;
use Phplrt\Parser\Grammar\ProductionInterface;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Grammar\TerminalInterface;
use Phplrt\Source\File;

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
final class Parser implements
    ParserInterface,
    ParserConfigsInterface,
    BuilderInterface,
    LexerInterface
{
    use ParserConfigsTrait;

    /**
     * @var non-empty-string
     */
    private const ERROR_XDEBUG_NOTICE_MESSAGE =
        'Please note that if Xdebug is enabled, a "Fatal error: Maximum function nesting level of "%d" ' .
        'reached, aborting!" errors may occur. In the second case, it is worth increasing the ini value ' .
        'or disabling the extension.';

    /**
     * @var non-empty-string
     */
    private const ERROR_BUFFER_TYPE = 'Buffer class should implement %s interface';

    /**
     * The lexer instance.
     *
     * @readonly
     */
    private LexerInterface $lexer;

    /**
     * The {@see SelectorInterface} is responsible for preparing
     * and analyzing the PHP environment for the parser to work.
     *
     * @readonly
     */
    private SelectorInterface $env;

    /**
     * Array of transition rules for the parser.
     *
     * @var array<array-key, RuleInterface>
     * @readonly
     */
    private array $rules = [];

    /**
     * Array of possible tokens for error or missing token.
     *
     * @var list<TokenInterface>
     */
    private array $possibleTokens = [];

    /**
     * @param iterable<array-key, RuleInterface> $grammar
     */
    public function __construct(
        LexerInterface $lexer,
        iterable $grammar = [],
        array $options = []
    ) {
        $this->lexer = $lexer;
        $this->env = new EnvironmentFactory();
        $this->initializeGrammar($grammar);
        $this->bootParserConfigsTrait($options);
    }

    /**
     * Initialize parser's grammar
     */
    private function initializeGrammar(iterable $grammar): void
    {
        $this->rules = $grammar instanceof \Traversable ? \iterator_to_array($grammar) : $grammar;
    }

    public function parse($source, array $options = []): iterable
    {
        if ($this->rules === []) {
            return [];
        }

        $this->env->prepare();

        try {
            return $this->run(File::new($source), $options);
        } finally {
            $this->env->rollback();
        }
    }

    /**
     * @return iterable<array-key, NodeInterface>
     * @throws \Throwable
     */
    private function run(ReadableInterface $source, array $options): iterable
    {
        $buffer = $this->createBuffer($this->lex($source));

        $context = $this->createExecutionContext($buffer, $source, $options);

        return $this->parseOrFail($context);
    }

    private function createBuffer(\Generator $stream): BufferInterface
    {
        \assert(
            \is_subclass_of($this->buffer, BufferInterface::class),
            \sprintf(self::ERROR_BUFFER_TYPE, BufferInterface::class)
        );

        $class = $this->buffer;

        $buffer = new $class($stream);

        if ($this->useMutableBuffer) {
            $buffer = new MutableBuffer($buffer);
        }

        return $buffer;
    }

    /**
     * @param int<0, max> $offset
     * @return \Generator|TokenInterface[]
     */
    public function lex($source, int $offset = 0): \Generator
    {
        try {
            foreach ($this->lexer->lex(File::new($source)) as $token) {
                yield $token;
            }
        } catch (RuntimeExceptionInterface $e) {
            throw UnrecognizedTokenException::fromLexerException($e);
        }
    }

    protected function createExecutionContext(
        BufferInterface $buffer,
        ReadableInterface $source,
        array $options
    ): Context {
        return new Context($buffer, $source, $this->initial, $options);
    }

    /**
     * @throws ParserRuntimeException
     */
    private function parseOrFail(Context $context): iterable
    {
        $result = $this->next($context);

        if (\is_iterable($result) && $this->isEoi($context->buffer)) {
            return $result;
        }

        $token = $context->lastOrdinalToken ?? $context->buffer->current();

        if ($this->possibleTokensSearching) {
            $this->findPossibleTokensForUnexpected($context);
        }

        throw UnexpectedTokenWithHintsException::fromToken($context->getSource(), $token, null, $this->possibleTokens);
    }

    private function findPossibleTokensForUnexpected(Context $context): void
    {
        $problemTokenOffset = $context->lastOrdinalToken->getOffset();
        $problemTokenKey = 0;
        while ($context->buffer->get($problemTokenKey)->getOffset() !== $problemTokenOffset) {
            ++$problemTokenKey;
        }

        $context->buffer->set($problemTokenKey, new Token(
            DriverInterface::UNKNOWN_TOKEN_NAME,
            '?',
            $problemTokenOffset
        ));
        if ($this->eoi === $context->lastOrdinalToken->getName()) {
            $context->buffer->set($problemTokenKey + 1, new EndOfInput($problemTokenOffset));
        }

        $this->next($context);
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

    /**
     * @return mixed
     */
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

                    if (
                        DriverInterface::UNKNOWN_TOKEN_NAME === $context->lastProcessedToken->getName()
                        && !\in_array($context->rule->token, $this->possibleTokens, true)
                    ) {
                        $this->possibleTokens[] = $context->rule->token;
                    }

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

                    if (! $context->rule->isKeep()) {
                        return [];
                    }
                }

                if (
                    DriverInterface::UNKNOWN_TOKEN_NAME === $context->lastProcessedToken->getName()
                    && !\in_array($context->rule->token, $this->possibleTokens, true)
                ) {
                    $this->possibleTokens[] = $context->rule->token;
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

    public function build(ContextInterface $context, $result)
    {
        return $result;
    }
}
