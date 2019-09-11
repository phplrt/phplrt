<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface as LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Parser\Builder\BuilderInterface;
use Phplrt\Parser\Rule\ProductionInterface;
use Phplrt\Parser\Rule\RuleInterface;
use Phplrt\Parser\Rule\TerminalInterface;

/**
 * A LL(k) recurrence recursive descent parser implementation.
 *
 * Is a kind of top-down parser built from a set of mutually recursive methods
 * defined in:
 *  - Phplrt\Parser\Rule\ProductionInterface::reduce()
 *  - Phplrt\Parser\Rule\TerminalInterface::reduce()
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
 * Vulnerable to left recursion, like:
 * <code>
 *      Digit = "0" | "1" | "2" | "3" | "4" | "5" | "6" | "7" | "8" | "9" ;
 *      Operator = "+" | "-" | "*" | "/" ;
 *      Number = Digit { Digit } ;
 *
 *      Expression = Number | Number Operator ;
 *      (*            ^^^^^^   ^^^^^^
 *          In this case, the grammar is incorrect and should be replaced by:
 *
 *          Expression = Number { Operator } ;
 *      *)
 * </code>
 */
abstract class AbstractParser implements ParserInterface
{
    /**
     * @var string
     */
    private const ERROR_XDEBUG_NOTICE_MESSAGE =
        'Please note that if Xdebug is enabled, a "Fatal error: Maximum function nesting level of "%d" ' .
        'reached, aborting!" errors may occur. In the second case, it is worth increasing the ini value ' .
        'or disabling the extension.';

    /**
     * Contains a token identifier that marks the end of the source.
     *
     * @var string|int
     */
    protected $eoi = TokenInterface::END_OF_INPUT;

    /**
     * The maximum number of tokens (lexemes) that are stored in the buffer.
     *
     * @var int
     */
    protected $buffer = 100;

    /**
     * @var array|RuleInterface[]
     */
    protected $rules = [];

    /**
     * @var BuilderInterface
     */
    protected $builder;

    /**
     * Contains the readonly token object which was last successfully processed
     * in the rules chain.
     *
     * It is required so that in case of errors it is possible to report that
     * it was on it that the problem arose.
     *
     * @var TokenInterface|null
     */
    private $token;

    /**
     * @var LexerInterface
     */
    private $lexer;

    /**
     * Parser constructor.
     *
     * @param LexerInterface $lexer
     * @param array|RuleInterface[] $rules
     */
    public function __construct(LexerInterface $lexer, array $rules = [])
    {
        $this->lexer = $lexer;
        $this->rules = $rules;

        $this->boot();

        $this->builder = $this->builder();
    }

    /**
     * @return void
     */
    protected function boot(): void
    {
        if (\function_exists('\\xdebug_is_enabled')) {
            @\trigger_error(\vsprintf(self::ERROR_XDEBUG_NOTICE_MESSAGE, [
                \ini_get('xdebug.max_nesting_level'),
            ]));
        }
    }

    /**
     * {@inheritDoc}
     */
    abstract public function builder(): BuilderInterface;

    /**
     * {@inheritDoc}
     * @throws \Throwable
     */
    public function parse($source): iterable
    {
        if (\count($this->rules) === 0) {
            return [];
        }

        try {
            $buffer = $this->createBuffer($source);
        } catch (LexerRuntimeExceptionInterface $e) {
            throw $this->onLexerError($e->getToken());
        }

        $starts = $this->getInitialRule($buffer, $this->rules);

        $result = $this->reduce($buffer, $starts);

        if ($result === null) {
            throw $this->onSyntaxError($this->getToken($buffer));
        }

        if (! $this->isEoi($buffer)) {
            throw $this->onSyntaxError($this->getToken($buffer));
        }

        return $result;
    }

    /**
     * @param string|resource $source
     * @return BufferInterface
     */
    private function createBuffer($source): BufferInterface
    {
        $stream = function ($source) {
            yield from $this->lexer->lex($source);
        };

        $buffer = $this->buffer($stream($source), $this->buffer);

        $this->token = $buffer->current();

        return $buffer;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function buffer(\Generator $stream, int $size): BufferInterface;

    /**
     * {@inheritDoc}
     */
    abstract public function onLexerError(TokenInterface $token): \Throwable;

    /**
     * {@inheritDoc}
     */
    abstract public function getInitialRule(BufferInterface $buffer, array $rules);

    /**
     * @param BufferInterface $buffer
     * @param int|string $state
     * @return iterable|TokenInterface|null
     */
    protected function reduce(BufferInterface $buffer, $state)
    {
        /** @var TokenInterface $token */
        [$rule, $token, $result] = [$this->rules[$state], $buffer->current(), null];

        $next = function ($state) use ($buffer) {
            return $this->reduce($buffer, $state);
        };

        switch (true) {
            case $rule instanceof ProductionInterface:
                $result = $rule->reduce($buffer, $next);

                break;

            case $rule instanceof TerminalInterface:
                if (($result = $rule->reduce($buffer, $next)) !== null) {
                    $buffer->next();
                }

                //
                // Capture the most recently processed token.
                // In case of a syntax error, it will be displayed as incorrect.
                //
                if ($buffer->current()->getOffset() > $this->token->getOffset()) {
                    $this->token = $buffer->current();
                }

                if ($result !== null && ! $rule->isKeep()) {
                    return [];
                }

                break;
        }

        if ($result !== null) {
            $result = $this->builder->build($rule, $token, $state, $result) ?? $result;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function onSyntaxError(TokenInterface $token): \Throwable;

    /**
     * @param BufferInterface $buffer
     * @return TokenInterface
     */
    private function getToken(BufferInterface $buffer): TokenInterface
    {
        return $this->token ?? $buffer->current();
    }

    /**
     * @param BufferInterface $buffer
     * @return bool
     */
    private function isEoi(BufferInterface $buffer): bool
    {
        return $buffer->current()->getName() === $this->eoi;
    }
}
