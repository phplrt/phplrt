<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceExceptionInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Lexer\Config\HandlerInterface;
use Phplrt\Lexer\Config\NullHandler;
use Phplrt\Lexer\Config\PassthroughHandler;
use Phplrt\Lexer\Config\PassthroughWhenTokenHandler;
use Phplrt\Lexer\Config\ThrowErrorHandler;
use Phplrt\Lexer\Exception\LexerException;
use Phplrt\Lexer\Exception\UnexpectedStateException;
use Phplrt\Lexer\Exception\EndlessRecursionException;
use Phplrt\Lexer\Token\EndOfInput;
use Phplrt\Source\File;
use Phplrt\Source\SourceFactory;

class Multistate implements PositionalLexerInterface
{
    /**
     * @var array<array-key, PositionalLexerInterface>
     */
    private array $states = [];

    /**
     * @var array-key|null
     */
    private $state;

    /**
     * @var array<non-empty-string|int<0, max>, array<non-empty-string, non-empty-string|int<0, max>>>
     */
    private array $transitions = [];

    private SourceFactoryInterface $sources;

    private HandlerInterface $onEndOfInput;

    /**
     * @param array<array-key, PositionalLexerInterface> $states
     * @param array<array-key, array<non-empty-string, array-key>> $transitions
     * @param array-key|null $state
     * @param HandlerInterface $onEndOfInput This setting is responsible for the
     *        operation of the terminal token ({@see EndOfInput}).
     *
     *        See also {@see OnEndOfInput} for more details.
     *
     *        Note that you can also define your own {@see HandlerInterface} to
     *        override behavior.
     * @param non-empty-string $eoi
     */
    public function __construct(
        array $states,
        array $transitions = [],
        $state = null,
        ?HandlerInterface $onEndOfInput = null,
        ?SourceFactoryInterface $sources = null
    ) {
        foreach ($states as $name => $data) {
            $this->setState($name, $data);
        }

        $this->transitions = $transitions;
        $this->state = $state;

        $this->onEndOfInput = $onEndOfInput ?? new PassthroughWhenTokenHandler(
            Lexer::DEFAULT_EOI_TOKEN_NAME,
        );

        $this->sources = $sources ?? new SourceFactory();
    }

    /**
     * @param array-key|null $state
     * @api
     */
    public function startsWith($state): self
    {
        assert(\is_string($state) || \is_int($state) || $state === null);

        $this->state = $state;

        return $this;
    }

    /**
     * @param array-key $name
     * @param array<non-empty-string, non-empty-string>|PositionalLexerInterface $data
     * @api
     */
    public function setState($name, $data): self
    {
        assert(\is_string($name) || \is_int($name));
        assert(\is_array($data) || $data instanceof PositionalLexerInterface);

        if (\is_array($data)) {
            $data = new Lexer($data);
        }

        $this->states[$name] = $data;

        return $this;
    }

    /**
     * @param array-key $name
     * @api
     */
    public function removeState($name): self
    {
        unset($this->states[$name]);

        return $this;
    }

    /**
     * @param non-empty-string $token
     * @param array-key $in
     * @param array-key $then
     * @api
     */
    public function when(string $token, $in, $then): self
    {
        $this->transitions[$in][$token] = $then;

        return $this;
    }

    /**
     * Returns a set of token objects from the passed source.
     *
     * @psalm-immutable This method may not be pure, but it does not change
     *                  the internal state of the lexer and can be used in
     *                  asynchronous and parallel computing.
     *
     * @param mixed $source Any source supported by the {@see SourceFactoryInterface::create()}.
     * @param int<0, max> $offset Offset, starting from which you should
     *         start analyzing the source.
     *
     * @return iterable<array-key, TokenInterface> List of analyzed tokens.
     *
     * @throws LexerExceptionInterface An error occurs before source processing
     *         starts, when the given source cannot be recognized or if the
     *         lexer settings contain errors.
     * @throws LexerRuntimeExceptionInterface An exception that occurs after
     *         starting the lexical analysis and indicates problems in the
     *         analyzed source.
     *
     * @psalm-suppress TypeDoesNotContainType
     */
    public function lex($source, int $offset = 0): iterable
    {
        try {
            $source = $this->sources->create($source);
        } catch (\Throwable $e) {
            throw LexerException::fromInternalError($e);
        }

        if ($this->states === []) {
            throw UnexpectedStateException::fromEmptyStates($source);
        }

        if ($this->state === null) {
            $this->state = \array_key_first($this->states);
        }

        $states = [];
        $state = null;

        do {
            $completed = true;

            /**
             * We save the offset for the state to prevent endless transitions
             * in the future.
             */
            $states[$state ??= $this->state] = $offset;

            /**
             * Checking the existence of the current state.
             */
            if (!isset($this->states[$state])) {
                /**
                 * @noinspection IssetArgumentExistenceInspection
                 * @psalm-suppress UndefinedVariable
                 */
                throw UnexpectedStateException::fromState($state, $source, $token ?? null);
            }

            $stream = $this->states[$state]->lex($source, $offset);

            /**
             * This cycle is necessary in order to capture the last token,
             * because in PHP, "local "loop variables have a function scope.
             *
             * That is, the "$token" variable will be available in the future.
             */
            foreach ($stream as $token) {
                yield $token;

                if ($this->onEndOfInput->handle($source, $token) !== null) {
                    return;
                }

                /**
                 * If there is a transition, then update the data and start lexing again.
                 *
                 * @var int|string $next
                 */
                if (($next = ($this->transitions[$state][$token->getName()] ?? null)) !== null) {
                    /**
                     * If at least one token has been returned at the moment, then
                     * further analysis should be continued already from the
                     * desired offset and state.
                     */
                    $state = $next;

                    $offset = $token->getBytes() + $token->getOffset();

                    /**
                     * If the same offset is repeatedly detected for this state,
                     * then at this stage there was an entrance to an endless cycle.
                     */
                    if (($states[$state] ?? null) === $offset) {
                        throw EndlessRecursionException::fromState($state, $source, $token);
                    }

                    /** @psalm-suppress UnusedVariable */
                    $completed = false;

                    continue 2;
                }
            }
        } while (!$completed);
    }
}
