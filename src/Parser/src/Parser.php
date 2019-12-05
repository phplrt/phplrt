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
use Phplrt\Parser\Builder\Common;
use Phplrt\Lexer\Buffer\ArrayBuffer;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Grammar\RuleInterface;
use Phplrt\Contracts\Lexer\BufferInterface;
use Phplrt\Parser\Builder\BuilderInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Grammar\TerminalInterface;
use Phplrt\Contracts\Grammar\ProductionInterface;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Contracts\Lexer\Exception\LexerRuntimeExceptionInterface;

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
class Parser implements ParserInterface
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
     * Contains the readonly token object which was last successfully processed
     * in the rules chain.
     *
     * It is required so that in case of errors it is possible to report that
     * it was on it that the problem arose.
     *
     * Note: This is a stateful data and may cause a race condition error. In
     * the future, it is necessary to delete this data with a replacement for
     * the stateless structure.
     *
     * @var TokenInterface|null
     */
    protected $token;

    /**
     * Contains the readonly NodeInterface object which was last successfully
     * processed while parsing.
     *
     * Note: This is a stateful data and may cause a race condition error. In
     * the future, it is necessary to delete this data with a replacement for
     * the stateless structure.
     *
     * @var NodeInterface|null
     */
    protected $node;

    /**
     * A buffer class that allows you to iterate over the stream of tokens and
     * return to the selected position.
     *
     * Initialized by the generator with tokens during parser launch.
     *
     * @var string
     */
    protected $buffer = ArrayBuffer::class;

    /**
     * An abstract syntax tree builder.
     *
     * @var BuilderInterface
     */
    protected $builder;

    /**
     * The initial state (initial rule identifier) of the parser.
     *
     * @var string|int|null
     */
    protected $initial;

    /**
     * The lexer instance.
     *
     * @var LexerInterface
     */
    protected $lexer;

    /**
     * Array of transition rules for the parser.
     *
     * @var array|RuleInterface[]
     */
    protected $rules;

    /**
     * Token indicating the end of parsing.
     *
     * @var string
     */
    private $eoi = TokenInterface::END_OF_INPUT;

    /**
     * Parser constructor.
     *
     * @param LexerInterface $lexer
     * @param iterable|RuleInterface[] $grammar
     * @param array $options
     */
    public function __construct(LexerInterface $lexer, iterable $grammar, array $options = [])
    {
        $this->lexer = $lexer;

        $this->bootGrammar($grammar);
        $this->bootConfigs($options);
        $this->boot();
    }

    /**
     * @param iterable|RuleInterface[] $grammar
     * @return void
     */
    private function bootGrammar(iterable $grammar): void
    {
        $this->rules = $grammar instanceof \Traversable ? \iterator_to_array($grammar) : $grammar;
    }

    /**
     * @param array $options
     * @return void
     */
    private function bootConfigs(array $options): void
    {
        $this->eoi = $options[static::CONFIG_EOI] ?? $this->eoi;
        $this->buffer = $options[static::CONFIG_BUFFER] ?? $this->buffer;
        $this->builder = $options[static::CONFIG_AST_BUILDER] ?? new Common();
        $this->initial = $options[static::CONFIG_INITIAL_RULE] ?? \array_key_first($this->rules);
    }

    /**
     * @return void
     */
    private function boot(): void
    {
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
    public function parse($source): iterable
    {
        if (\count($this->rules) === 0) {
            return [];
        }

        return $this->run(File::new($source));
    }

    /**
     * @param ReadableInterface $source
     * @return iterable
     * @throws \Throwable
     */
    private function run(ReadableInterface $source): iterable
    {
        $buffer = $this->getBuffer($this->doLex($source));

        $this->reset($buffer);

        return $this->parseOrFail($source, $buffer);
    }

    /**
     * @param \Generator $stream
     * @return BufferInterface
     */
    private function getBuffer(\Generator $stream): BufferInterface
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

        return (static function () use ($result) {
            yield from $result;
        })();
    }

    /**
     * @param BufferInterface $buffer
     * @return void
     */
    private function reset(BufferInterface $buffer): void
    {
        $this->token = $buffer->current();
        $this->node = null;
    }

    /**
     * @param ReadableInterface $source
     * @param BufferInterface $buffer
     * @return iterable
     * @throws \Throwable
     */
    private function parseOrFail(ReadableInterface $source, BufferInterface $buffer): iterable
    {
        $result = $this->next($source, $buffer, $this->initial);

        if (\is_iterable($result) && $this->isEoi($buffer)) {
            return $result;
        }

        $message = \vsprintf(ParserRuntimeException::ERROR_UNEXPECTED_TOKEN, [
            $this->render($this->token ?? $buffer->current()),
        ]);

        throw new ParserRuntimeException($message, $this->token ?? $buffer->current(), $this->node);
    }

    /**
     * @param ReadableInterface $source
     * @param BufferInterface $buffer
     * @param string|int|mixed $state
     * @return mixed
     */
    protected function next(ReadableInterface $source, BufferInterface $buffer, $state)
    {
        return $this->reduce($source, $buffer, $state);
    }

    /**
     * @param ReadableInterface $source
     * @param BufferInterface $buffer
     * @param int|string $state
     * @return iterable|TokenInterface|null
     */
    private function reduce(ReadableInterface $source, BufferInterface $buffer, $state)
    {
        /** @var TokenInterface $token */
        [$rule, $result, $token] = [$this->rules[$state], null, $buffer->current()];

        switch (true) {
            case $rule instanceof ProductionInterface:
                $result = $rule->reduce($buffer, function ($state) use ($source, $buffer) {
                    return $this->next($source, $buffer, $state);
                });

                break;

            case $rule instanceof TerminalInterface:
                $result = $rule->reduce($buffer);

                if ($result !== null) {
                    $buffer->next();

                    $this->spotTerminal($buffer);

                    if (! $rule->isKeep()) {
                        return [];
                    }
                }

                break;
        }

        if ($result === null) {
            return null;
        }

        return $this->buildAst($source, $token, $state, $result);
    }

    /**
     * Capture the most recently processed token.
     * In case of a syntax error, it will be displayed as incorrect.
     *
     * @param BufferInterface $buffer
     * @return void
     */
    private function spotTerminal(BufferInterface $buffer): void
    {
        if ($buffer->current()->getOffset() > $this->token->getOffset()) {
            $this->token = $buffer->current();
        }
    }

    /**
     * @param ReadableInterface $file
     * @param TokenInterface $token
     * @param int|string $state
     * @param mixed $result
     * @return mixed|null
     */
    private function buildAst(ReadableInterface $file, TokenInterface $token, $state, $result)
    {
        $result = $this->builder->build($file, $this->rules[$state], $token, $state, $result) ?? $result;

        if ($result instanceof NodeInterface) {
            $this->node = $result;
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
