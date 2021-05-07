<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Buffer\BufferInterface;
use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Grammar\ProductionInterface;
use Phplrt\Contracts\Grammar\RuleInterface;
use Phplrt\Contracts\Grammar\TerminalInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Config\Config;
use Phplrt\Parser\Config\ConfigInterface;
use Phplrt\Parser\Config\Options;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Parser\Exception\UnexpectedTokenException;
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
 *
 * @psalm-import-type ConfigArray from Config
 * @psalm-import-type StepReducer from ConfigInterface
 */
final class Parser implements ParserInterface, Options
{
    /**
     * @var string
     */
    private const ERROR_XDEBUG_NOTICE_MESSAGE =
        'Please note that if Xdebug is enabled, a "Fatal error: Maximum function nesting level of "%d" ' .
        'reached, aborting!" errors may occur. In the second case, it is worth increasing the ini value ' .
        'or disabling the extension.';

    /**
     * The lexer instance.
     *
     * @var LexerInterface
     */
    private LexerInterface $lexer;

    /**
     * Array of transition rules for the parser.
     *
     * @var array<string|int, RuleInterface>
     */
    private array $rules;

    /**
     * @var ConfigInterface
     */
    private ConfigInterface $config;

    /**
     * @var string
     */
    private string $eoi;

    /**
     * @var StepReducer|null
     */
    private $step;

    /**
     * @var BuilderInterface|null
     */
    private ?BuilderInterface $builder;

    /**
     * @param LexerInterface $lexer
     * @param iterable<string|int, RuleInterface> $grammar
     * @param ConfigArray|ConfigInterface $options
     */
    public function __construct(LexerInterface $lexer, iterable $grammar = [], $options = [])
    {
        $this->lexer = $lexer;
        $this->rules = $this->bootGrammar($grammar);
        $this->config = $this->bootConfig($options);

        $this->bootEnvironment();
        $this->bootConfigDefaults();
    }

    /**
     * @return void
     */
    private function bootConfigDefaults(): void
    {
        $this->eoi = $this->config->getEoiTokenName();
        $this->step = $this->config->getStepReducer();
        $this->builder = $this->config->getBuilder();
    }

    /**
     * @param iterable<string|int, RuleInterface> $grammar
     * @return array<string|int, RuleInterface>
     */
    private function bootGrammar(iterable $grammar): array
    {
        if ($grammar instanceof \Traversable) {
            return \iterator_to_array($grammar);
        }

        return $grammar;
    }

    /**
     * @param ConfigArray|ConfigInterface $options
     * @return ConfigInterface
     */
    private function bootConfig($options): ConfigInterface
    {
        if ($options instanceof ConfigInterface) {
            return $options;
        }

        return new Config($options);
    }

    /**
     * In the case that the xdebug is enabled, then the parser may return
     * an error due to the features of the recursive algorithm.
     *
     * Parser should notify about it.
     */
    private function bootEnvironment(): void
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
    public function parse($source, array $options = []): iterable
    {
        if (\count($this->rules) === 0) {
            return [];
        }

        return $this->parseOrFail(
            $this->createContext(File::new($source), $options)
        );
    }

    /**
     * @param Context $context
     * @return iterable
     * @throws ParserRuntimeException
     */
    private function parseOrFail(Context $context): iterable
    {
        $result = $this->next($context);
        $current = $context->buffer->current();

        if (\is_iterable($result) && $current->getName() === $this->eoi) {
            return $result;
        }

        $token = $context->lastOrdinalToken ?? $current;

        throw UnexpectedTokenException::fromToken($context->getSource(), $token);
    }

    /**
     * @param Context $context
     * @return array|mixed|null
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

        if ($this->builder !== null) {
            $result = $this->builder->build($context, $result) ?? $result;
        }

        if ($result instanceof NodeInterface) {
            $context->node = $result;
        }

        return $result;
    }

    /**
     * @param ReadableInterface $source
     * @param array $options
     * @return Context
     * @throws RuntimeExceptionInterface
     */
    private function createContext(ReadableInterface $source, array $options): Context
    {
        $buffer = $this->config->getBuffer($this->lexer->lex($source));
        $initial = $this->config->getInitialRule($this->rules);

        return new Context($buffer, $source, $initial, $options);
    }
}
