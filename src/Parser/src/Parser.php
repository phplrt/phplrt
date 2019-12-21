<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Source\File;
use Phplrt\Lexer\Token\Renderer;
use Phplrt\Lexer\Buffer\ArrayBuffer;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Grammar\RuleInterface;
use Phplrt\Contracts\Lexer\BufferInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Parser\BuilderInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Grammar\TerminalInterface;
use Phplrt\Contracts\Grammar\ProductionInterface;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Contracts\Lexer\Exception\LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Parser\Exception\ParserRuntimeExceptionInterface;

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
final class Parser implements ParserInterface
{
    /**
     * @var string
     */
    public const CONFIG_INITIAL_RULE = 'initial';

    /**
     * @var string
     */
    public const CONFIG_AST_BUILDER = 'builder';

    /**
     * @var string
     */
    public const CONFIG_BUFFER = 'buffer';

    /**
     * @var string
     */
    public const CONFIG_EOI = 'eoi';

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
     * A buffer class that allows you to iterate over the stream of tokens and
     * return to the selected position.
     *
     * Initialized by the generator with tokens during parser launch.
     *
     * @var string
     */
    private string $buffer = ArrayBuffer::class;

    /**
     * An abstract syntax tree builder.
     *
     * @var BuilderInterface|null
     */
    private ?BuilderInterface $builder = null;

    /**
     * The initial state (initial rule identifier) of the parser.
     *
     * @var string|int|null
     */
    private $initial;

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
     * Token indicating the end of parsing.
     *
     * @var string
     */
    private string $eoi = TokenInterface::END_OF_INPUT;

    /**
     * Parser constructor.
     *
     * @param LexerInterface $lexer
     * @param iterable|RuleInterface[] $grammar
     * @param array $options
     */
    public function __construct(LexerInterface $lexer, iterable $grammar = [], array $options = [])
    {
        $this->lexer = $lexer;

        //
        // Initialize parser's grammar
        //
        $this->rules = $grammar instanceof \Traversable ? \iterator_to_array($grammar) : $grammar;

        //
        // Initialize parser's configuration options
        //
        $this->eoi = $options[static::CONFIG_EOI] ?? $this->eoi;
        $this->buffer = $options[static::CONFIG_BUFFER] ?? $this->buffer;
        $this->initial = $options[static::CONFIG_INITIAL_RULE] ?? \array_key_first($this->rules);
        $this->builder = $options[static::CONFIG_AST_BUILDER] ?? null;

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
     * @throws LexerRuntimeExceptionInterface
     * @throws \Throwable
     */
    private function run(ReadableInterface $source, array $options): iterable
    {
        $buffer = $this->createBuffer($this->doLex($source));

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
     * @return \Generator|TokenInterface[]
     * @throws LexerRuntimeExceptionInterface
     */
    private function doLex(ReadableInterface $source): \Generator
    {
        $result = $this->lexer->lex($source);

        if ($result instanceof \Generator) {
            return $result;
        }

        return (fn(): \Generator => yield from $result)();
    }

    /**
     * @param BufferInterface $buffer
     * @param ReadableInterface $source
     * @param array $options
     * @return Context
     */
    private function createExecutionContext(BufferInterface $buffer, ReadableInterface $source, array $options): Context
    {
        return new Context($buffer, $source, $this->initial, $options);
    }

    /**
     * @param Context $context
     * @return iterable
     * @throws ParserRuntimeExceptionInterface
     */
    private function parseOrFail(Context $context): iterable
    {
        $result = $this->next($context);

        if (\is_iterable($result) && $this->isEoi($context->buffer)) {
            return $result;
        }

        throw $this->createParserError($context);
    }

    /**
     * @param Context $context
     * @return ParserRuntimeExceptionInterface|ParserRuntimeException
     */
    private function createParserError(Context $context): ParserRuntimeExceptionInterface
    {
        $message = \vsprintf(ParserRuntimeException::ERROR_UNEXPECTED_TOKEN, [
            $this->render($context->lastOrdinalToken ?? $context->buffer->current()),
        ]);

        $lastToken = $context->lastOrdinalToken ?? $context->lastProcessedToken;

        return new ParserRuntimeException($message, $lastToken, $context->node);
    }

    /**
     * @param Context $context
     * @return array|mixed|null
     */
    private function next(Context $context)
    {
        [$context->rule, $result] = [$this->rules[$context->state], null];

        switch (true) {
            case $context->rule instanceof ProductionInterface:
                $result = $context->rule->reduce($context->buffer, function ($state) use ($context) {
                    $context->state = $state;

                    return $this->next($context);
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

        if ($this->builder) {
            $result = $this->builder->build($context, $result) ?? $result;
        }

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
     * @param TokenInterface $token
     * @return string
     */
    private function render(TokenInterface $token): string
    {
        if (\class_exists(Renderer::class)) {
            return (new Renderer())->render($token);
        }

        return '"' . $token->getValue() . '"';
    }
}
