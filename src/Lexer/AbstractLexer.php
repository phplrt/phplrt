<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Lexer\Token\Token;
use Phplrt\Lexer\Token\Unknown;
use Phplrt\Lexer\State\Markers;
use Phplrt\Lexer\Token\BaseToken;
use Phplrt\Lexer\Token\EndOfInput;
use Phplrt\Lexer\State\StateInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Exception\LexerException;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Exception\LexerRuntimeException;
use Phplrt\Lexer\Exception\UnexpectedStateException;
use Phplrt\Lexer\Exception\EndlessRecursionException;
use Phplrt\Lexer\Exception\UnrecognizedTokenException;

/**
 * Class AbstractLexer
 */
abstract class AbstractLexer implements LexerInterface
{
    /**
     * @var string
     */
    private const DEFAULT_STATE_DRIVER = Markers::class;

    /**
     * @var string
     */
    private const ERROR_EMPTY_STATES = 'Can not start lexical analysis, because lexer was not initialized';

    /**
     * @var string
     */
    private const ERROR_UNRECOGNIZED_TOKEN = 'Syntax error, unrecognized %s ';

    /**
     * @var string
     */
    private const ERROR_ENDLESS_TRANSITIONS = 'An unsolvable infinite lexer state transitions was found at %s';

    /**
     * @var string
     */
    private const ERROR_UNEXPECTED_STATE = 'Unrecognized token state #%s';

    /**
     * @var string
     */
    private const ERROR_STATE_DATA = 'Lexer state #%s data should be an array or instance of %s, but %s given';

    /**
     * @var string
     */
    private const ERROR_ARGUMENT_TYPE = 'A $source argument should be a resource or string type, but %s given';

    /**
     * @var array|StateInterface[]
     */
    protected $states = [];

    /**
     * @var string|int|null
     */
    protected $initial;

    /**
     * @var TokenInterface|null
     */
    private $last;

    /**
     * @var array|StateInterface[]
     */
    private $drivers;

    /**
     * AbstractLexer constructor.
     */
    public function __construct()
    {
        $this->drivers = $this->bootStates($this->states);
    }

    /**
     * @param array $states
     * @return array
     */
    private function bootStates(array $states): array
    {
        $result = [];

        foreach ($states as $id => $data) {
            $result[$id] = $this->bootState($data, $id);
        }

        if (\count($result) === 0) {
            throw new LexerException(self::ERROR_EMPTY_STATES);
        }

        return $result;
    }

    /**
     * @param mixed $payload
     * @param mixed $id
     * @return StateInterface
     */
    private function bootState($payload, $id): StateInterface
    {
        switch (true) {
            case $payload instanceof StateInterface:
                return $payload;

            case \is_array($payload):
                /** @noinspection LoopWhichDoesNotLoopInspection */
                foreach ($payload as $value) {
                    if (! \is_string($value)) {
                        break;
                    }

                    return $this->createDriver($payload);
                }

                return $this->createDriver(...\array_values($payload));

            default:
                $message = \sprintf(self::ERROR_STATE_DATA, $id, StateInterface::class, \gettype($payload));

                throw new LexerException($message);
        }
    }

    /**
     * @param mixed ...$args
     * @return StateInterface
     */
    protected function createDriver(...$args): StateInterface
    {
        $driver = self::DEFAULT_STATE_DRIVER;

        return new $driver(...$args);
    }

    /**
     * {@inheritDoc}
     *
     * @param string|resource $source
     * @return TokenInterface[]|BaseToken[]
     * @throws LexerException
     * @throws LexerRuntimeException
     */
    public function lex($source): iterable
    {
        $stream = $this->execute($this->read($source), $this->getInitialStateIdentifier());

        while ($stream->valid()) {
            /** @var TokenInterface $token */
            $this->last = $token = $stream->current();

            if ($token->getType() === Unknown::ID) {
                $message = \sprintf(self::ERROR_UNRECOGNIZED_TOKEN, $token);
                throw new UnrecognizedTokenException($message, $token);
            }

            yield $token;

            $stream->next();
        }

        yield new EndOfInput(isset($token) ? $token->getOffset() + $token->getBytes() : 0);
    }

    /**
     * @param string $content
     * @param int $state
     * @param int $offset
     * @return \Generator|TokenInterface[]
     * @throws LexerRuntimeException
     */
    private function execute(string $content, int $state, int $offset = 0): \Generator
    {
        /**
         * We save the offset for the state to prevent endless transitions
         * in the future.
         */
        $states[$state] = $offset;

        execution:

        /**
         * Checking the existence of the current state.
         */
        if (! isset($this->drivers[$state])) {
            $message = \sprintf(self::ERROR_UNEXPECTED_STATE, $state);
            throw new UnexpectedStateException($message, $this->getToken());
        }

        /**
         * This cycle is necessary in order to capture the last token,
         * because in PHP, "local "loop variables have a function scope.
         *
         * That is, the "$token" variable will be available in the future.
         */
        foreach ($stream = $this->drivers[$state]->execute($content, $offset) as $token) {
            yield $token;
        }

        /**
         * If the generator returns something like integer, it means a
         * forced transition to a new state.
         *
         * @noinspection CallableParameterUseCaseInTypeContextInspection
         */
        if (\is_int($state = $stream->getReturn())) {

            /**
             * If the same offset is repeatedly detected for this state,
             * then at this stage there was an entrance to an endless cycle.
             */
            if (($states[$state] ?? null) === $offset) {
                $message = \sprintf(self::ERROR_ENDLESS_TRANSITIONS, $this->getToken());
                throw new EndlessRecursionException($message, $this->getToken());
            }

            $states[$state] = $offset;

            /**
             * If at least one token has been returned at the moment, then
             * further analysis should be continued already from the
             * desired offset.
             */
            if (isset($token)) {
                $offset = $token->getOffset() + $token->getBytes();
            }

            /**
             * The label expression used to reduce recursive invocation, like:
             *
             * <code>
             *  yield from $this->execute($src, $state, $content, $offset);
             * </code>
             *
             * In this case, the call stack remains unchanged and cannot be
             * overflowed. Otherwise, you may get an error like:
             * "Maximum function nesting level of '100' reached, aborting!".
             */
            goto execution;
        }
    }

    /**
     * @return TokenInterface
     */
    private function getToken(): TokenInterface
    {
        if ($this->last === null) {
            return new Token(TokenInterface::TYPE_SKIP, '', 0);
        }

        return $this->last;
    }

    /**
     * @param string|resource $source
     * @return string
     */
    private function read($source): string
    {
        switch (true) {
            case \is_resource($source):
                return \stream_get_contents($source);

            case \is_string($source):
                return $source;

            default:
                throw new \TypeError(\sprintf(self::ERROR_ARGUMENT_TYPE, \gettype($source)));
        }
    }

    /**
     * @return int|mixed|string
     */
    private function getInitialStateIdentifier()
    {
        if ($this->initial !== null && isset($this->drivers[$this->initial])) {
            return $this->initial;
        }

        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ($this->drivers as $key => $state) {
            return $key;
        }

        throw new LexerException(self::ERROR_EMPTY_STATES);
    }
}
