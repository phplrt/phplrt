<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Parser\Buffer\EagerBuffer;
use Phplrt\Parser\Rule\RuleInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Parser\Rule\TerminalInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Parser\Rule\ProductionInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Exception\ParserException;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface as LexerRuntimeExceptionInterface;

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
     * @var string
     */
    private const ERROR_UNEXPECTED = 'Syntax error, unexpected %s';

    /**
     * @var string
     */
    private const ERROR_UNRECOGNIZED = 'Syntax error, unrecognized lexeme %s';

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
     * Contains a token identifier that is excluded from analysis.
     *
     * @var int
     */
    protected $skip = TokenInterface::TYPE_SKIP;

    /**
     * Contains a token identifier that marks the end of the source.
     *
     * @var int
     */
    protected $eoi = TokenInterface::TYPE_END_OF_INPUT;

    /**
     * The maximum number of tokens (lexemes) that are stored in the buffer.
     *
     * @var int
     */
    protected $buffer = 100;

    /**
     * The initial identifier of the rule with which parsing begins.
     *
     * @var int
     */
    protected $initial = 0;

    /**
     * @var array|RuleInterface[]
     */
    protected $rules = [];

    /**
     * @var LexerInterface
     */
    private $lexer;

    /**
     * Parser constructor.
     *
     * @param LexerInterface $lexer
     */
    public function __construct(LexerInterface $lexer)
    {
        $this->lexer = $lexer;

        $this->detectDebuggers();
    }

    /**
     * @return void
     */
    private function detectDebuggers(): void
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
    public function parse($src): iterable
    {
        $this->token = null;

        try {
            $buffer = $this->buffer($this->tokenize($src), $this->buffer);

            $this->token = $buffer->current();
        } catch (LexerRuntimeExceptionInterface $e) {
            throw $this->lexError($e->getToken());
        } catch (\Exception|LexerExceptionInterface $e) {
            throw new ParserException($e->getMessage(), $e->getCode(), $e);
        }

        if (($result = $this->reduce($buffer, $this->initial)) === null) {
            throw $this->syntaxError($this->getToken($buffer));
        }

        if ($buffer->current()->getType() !== $this->eoi) {
            throw $this->syntaxError($this->getToken($buffer));
        }

        return $this->normalize($result);
    }

    /**
     * Method that converts token stream to buffer of lexemes.
     *
     * @param \Generator|TokenInterface[] $stream
     * @param int $size
     * @return BufferInterface|TokenInterface[]
     */
    protected function buffer(\Generator $stream, int $size): BufferInterface
    {
        return new EagerBuffer($stream);
    }

    /**
     * Returns a stream of tokens, excluding ignored ones.
     *
     * @param string|resource $src
     * @return \Generator
     * @throws LexerExceptionInterface
     * @throws LexerRuntimeExceptionInterface
     */
    private function tokenize($src): \Generator
    {
        foreach ($this->lexer->lex($src) as $token) {
            if ($token->getType() !== $this->skip) {
                yield $token;
            }
        }
    }

    /**
     * @param TokenInterface $token
     * @return \Exception|ParserRuntimeException
     */
    protected function lexError(TokenInterface $token): \Exception
    {
        return new ParserRuntimeException(\sprintf(self::ERROR_UNRECOGNIZED, $token));
    }

    /**
     * @param BufferInterface $buffer
     * @param int $state
     * @return iterable|TokenInterface|null
     */
    private function reduce(BufferInterface $buffer, int $state)
    {
        [$rule, $token, $result] = [$this->rules[$state], $buffer->current(), null];

        switch (true) {
            case $token->getType() === $this->eoi:
                $result = null;
                break;

            case $rule instanceof ProductionInterface:
                $result = $rule->reduce($buffer, $state, $token->getOffset(), function (int $state) use ($buffer) {
                    return $this->reduce($buffer, $state);
                });
                break;

            case $rule instanceof TerminalInterface:
                if ($result = $rule->reduce($buffer)) {
                    $buffer->next();
                }

                //
                // Capture the most recently processed token.
                // In case of a syntax error, it will be displayed as incorrect.
                //
                if ($buffer->current()->getOffset() > $this->token->getOffset()) {
                    $this->token = $buffer->current();
                }

                break;
        }

        return $result;
    }

    /**
     * @param TokenInterface $token
     * @return ParserRuntimeException|\Exception
     */
    protected function syntaxError(TokenInterface $token): \Exception
    {
        return new ParserRuntimeException(\sprintf(self::ERROR_UNEXPECTED, $token));
    }

    /**
     * @param BufferInterface $buffer
     * @return TokenInterface
     */
    private function getToken(BufferInterface $buffer): TokenInterface
    {
        return $this->token ?? $buffer->current();
    }

    /**
     * A helper method that converts the returned data to the correct format.
     * <code>
     *  class MyParser extends AbstractParser
     *  {
     *      public function parse($src): iterable
     *      {
     *          return $this->normalize(
     *              $this->doParse($src)
     *          );
     *      }
     *  }
     * </code>
     *
     * @param iterable|NodeInterface|NodeInterface[] $payload
     * @return iterable|NodeInterface|NodeInterface[]
     */
    protected function normalize(iterable $payload): iterable
    {
        if ($payload instanceof NodeInterface) {
            return $payload;
        }

        $result = [];

        foreach ($payload as $item) {
            if ($item instanceof NodeInterface) {
                $result[] = $item;
            }
        }

        return $result;
    }
}







































