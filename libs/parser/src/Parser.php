<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Parser\Exception\UnexpectedTokenException;
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
     * @var string
     */
    private const ERROR_XDEBUG_NOTICE_MESSAGE =
        'Please note that if Xdebug is enabled, a "Fatal error: Maximum function nesting level of "%d" ' .
        'reached, aborting!" errors may occur. In the second case, it is worth increasing the ini value ' .
        'or disabling the extension.';

    /**
     * @var string
     */
    private const ERROR_BUFFER_TYPE = 'Buffer class should implement %s interface';

    /**
     * The lexer instance.
     *
     * @var LexerInterface
     */
    private LexerInterface $lexer;

    /**
     * Array of transition rules for the parser.
     *
     * @var array|RuleInterface[]
     */
    private array $rules;

    /**
     * Parser constructor.
     *
     * @param LexerInterface $lexer
     * @param iterable|RuleInterface[] $grammar
     * @param array $options
     */
    public function __construct(LexerInterface $lexer, iterable $grammar = [], array $options = [])
    {
        $this->initializeLexer($lexer);
        $this->initializeGrammar($grammar);
        $this->initializeOptions($options);
    }

    /**
     * @param LexerInterface $lexer
     * @return void
     */
    private function initializeLexer(LexerInterface $lexer): void
    {
        $this->lexer = $lexer;
    }

    /**
     * Initialize parser's grammar
     *
     * @param iterable $grammar
     * @return void
     */
    private function initializeGrammar(iterable $grammar): void
    {
        $this->rules = $grammar instanceof \Traversable ? \iterator_to_array($grammar) : $grammar;
    }

    /**
     * @param array $options
     * @return void
     */
    private function initializeOptions(array $options): void
    {
        $this->bootParserConfigsTrait($options);

        //
        // In the case that the xdebug is enabled, then the parser may return
        // an error due to the features of the recursive algorithm.
        //
        // Parser should notify about it.
        //
        if (\function_exists('\\xdebug_is_enabled')) {
            @\trigger_error(\vsprintf(self::ERROR_XDEBUG_NOTICE_MESSAGE, [
                \ini_get('xdebug.max_nesting_level'),
            ]));
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param string|resource|ReadableInterface|mixed $source
     * @throws \Throwable
     */
    public function parse($source, array $options = []): iterable
    {
        if (\count($this->rules) === 0) {
            return [];
        }

        return $this->run(File::new($source), $options);
    }

    /**
     * @param ReadableInterface $source
     * @param array $options
     * @return iterable
     * @throws \Throwable
     */
    private function run(ReadableInterface $source, array $options): iterable
    {
        $buffer = $this->createBuffer($this->lex($source));

        $context = $this->createExecutionContext($buffer, $source, $options);

        return $this->parseOrFail($context);
    }

    /**
     * @param \Generator $stream
     * @return BufferInterface
     */
    private function createBuffer(\Generator $stream): BufferInterface
    {
        \assert(
            \is_subclass_of($this->buffer, BufferInterface::class),
            \sprintf(self::ERROR_BUFFER_TYPE, BufferInterface::class)
        );

        $class = $this->buffer;

        return new $class($stream);
    }

    /**
     * @param ReadableInterface $source
     * @param int $offset
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

    /**
     * @param BufferInterface $buffer
     * @param ReadableInterface $source
     * @param array $options
     * @return Context
     */
    protected function createExecutionContext(
        BufferInterface $buffer,
        ReadableInterface $source,
        array $options
    ): Context {
        return new Context($buffer, $source, $this->initial, $options);
    }

    /**
     * @param Context $context
     * @return iterable
     * @throws ParserRuntimeException
     */
    private function parseOrFail(Context $context): iterable
    {
        $result = $this->next($context);

        if (\is_iterable($result) && $this->isEoi($context->buffer)) {
            return $result;
        }

        $token = $context->lastOrdinalToken ?? $context->buffer->current();

        throw UnexpectedTokenException::fromToken($context->getSource(), $token);
    }

    /**
     * @param Context $context
     * @return array|mixed|null
     */
    private function next(Context $context)
    {
        if ($this->step) {
            return ($this->step)($context, function () use ($context) {
                return $this->runNextStep($context);
            });
        }

        return $this->runNextStep($context);
    }

    /**
     * @param Context $context
     * @return array|mixed|null
     */
    private function runNextStep(Context $context)
    {
        [$context->rule, $result] = [$this->rules[$context->state], null];

        switch (true) {
            case $context->rule instanceof ProductionInterface:
                $result = $context->rule->reduce($context->buffer, function ($state) use ($context) {
                    // Keep current state
                    $before = [$context->state, $context->lastProcessedToken];
                    // Update state
                    [$context->state, $context->lastProcessedToken] = [$state, $context->buffer->current()];

                    $result = $this->next($context);

                    // Rollback previous state
                    [$context->state, $context->lastProcessedToken] = $before;

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
     *
     * @param BufferInterface $buffer
     * @return bool
     */
    private function isEoi(BufferInterface $buffer): bool
    {
        $current = $buffer->current();

        return $current->getName() === $this->eoi;
    }

    /**
     * {@inheritDoc}
     */
    public function build(ContextInterface $context, $result)
    {
        return $result;
    }
}
