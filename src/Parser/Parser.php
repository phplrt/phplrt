<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Ast\Leaf;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface as LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Parser\Buffer\EagerBuffer;
use Phplrt\Parser\Exception\ParserException;
use Phplrt\Parser\Exception\ParserRuntimeException;
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
class Parser implements ParserInterface
{
    use Facade;

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
     * @param array|RuleInterface[] $rules
     * @param string|int $initial
     */
    public function __construct(LexerInterface $lexer, array $rules = [], $initial = null)
    {
        $this->lexer = $lexer;
        $this->rules = $rules;
        $this->initial = $initial ?? $this->initial;

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
        if (\count($this->rules) === 0) {
            return [];
        }

        $buffer = $this->createBuffer($src);

        if (($result = $this->next($buffer, $this->initial)) === null) {
            throw $this->syntaxError($this->getToken($buffer));
        }

        if (! $this->isEoi($buffer)) {
            throw $this->syntaxError($this->getToken($buffer));
        }

        return $this->normalize($result);
    }

    /**
     * @param string|resource $src
     * @return BufferInterface
     * @throws ParserException
     * @throws ParserRuntimeException
     */
    private function createBuffer($src): BufferInterface
    {
        try {
            $buffer = $this->buffer($this->lexer->lex($src), $this->buffer);

            $this->token = $buffer->current();
        } catch (LexerRuntimeExceptionInterface $e) {
            throw $this->lexError($e->getToken());
        } catch (\Exception | LexerExceptionInterface $e) {
            throw new ParserException($e->getMessage(), $e->getCode(), $e);
        }

        return $buffer;
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
     * @param iterable|TokenInterface $children
     * @param int $offset
     * @param int|string $type
     * @return mixed
     */
    protected function reduce($children, int $offset, $type)
    {
        if (isset($this->reducers[$type])) {
            return $this->reducers[$type]($children, $offset, $type);
        }

        return $children;
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
     * @return bool
     */
    private function isEoi(BufferInterface $buffer): bool
    {
        return $buffer->current()->getName() === $this->eoi;
    }

    /**
     * @param BufferInterface $buffer
     * @param int|string $state
     * @return iterable|TokenInterface|null
     */
    private function next(BufferInterface $buffer, $state)
    {
        /** @var TokenInterface $token */
        [$rule, $token, $result] = [$this->rules[$state], $buffer->current(), null];

        $next = function ($state) use ($buffer) {
            return $this->next($buffer, $state);
        };

        switch (true) {
            case $rule instanceof ProductionInterface:
                $result = $rule->reduce($buffer, $next);

                break;

            case $rule instanceof TerminalInterface:
                if ($result = $rule->reduce($buffer, $next)) {
                    $buffer->next();

                    $result = $rule->isKeep() ? $result : [];
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

        if ($result !== null) {
            $result = $this->reduce($result, $token->getOffset(), $state);
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
            $result[] = $item;
        }

        return $result;
    }
}
