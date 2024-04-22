<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Lexer\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Source\SourceExceptionInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Lexer\Compiler\Markers as MarkersCompiler;
use Phplrt\Lexer\Config\HandlerInterface;
use Phplrt\Lexer\Config\NullHandler;
use Phplrt\Lexer\Config\PassthroughHandler;
use Phplrt\Lexer\Config\ThrowErrorHandler;
use Phplrt\Lexer\Exception\LexerException;
use Phplrt\Lexer\Token\UnknownToken;
use Phplrt\Lexer\Driver\Markers;
use Phplrt\Lexer\Token\EndOfInput;
use Phplrt\Lexer\Driver\DriverInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\SourceFactory;

/**
 * @final
 */
class Lexer implements PositionalLexerInterface, MutableLexerInterface
{
    /**
     * Default token name for unidentified tokens.
     *
     * @var non-empty-string
     * @final
     */
    public const DEFAULT_UNKNOWN_TOKEN_NAME = UnknownToken::DEFAULT_TOKEN_NAME;

    /**
     * Default token name for end of input.
     *
     * @var non-empty-string
     * @final
     */
    public const DEFAULT_EOI_TOKEN_NAME = EndOfInput::DEFAULT_TOKEN_NAME;

    /**
     * @var array<array-key, non-empty-string>
     */
    protected array $tokens = [];

    /**
     * @var list<non-empty-string>
     */
    protected array $skip = [];

    private DriverInterface $driver;

    private HandlerInterface $onHiddenToken;

    private HandlerInterface $onUnknownToken;

    private HandlerInterface $onEndOfInput;

    /**
     * @var non-empty-string
     * @readonly
     */
    private string $unknown;

    /**
     * @var non-empty-string
     * @readonly
     */
    private string $eoi;

    /**
     * @readonly
     */
    private SourceFactoryInterface $sources;

    /**
     * @param array<array-key, non-empty-string> $tokens List of
     *        token names/identifiers and its patterns.
     * @param list<array-key> $skip List of hidden token names/identifiers.
     * @param HandlerInterface $onUnknownToken This setting is responsible for
     *        the behavior of the lexer in case of detection of unrecognized
     *        tokens.
     *
     *        See {@see OnUnknownToken} for more details.
     *
     *        Note that you can also define your own {@see HandlerInterface} to
     *        override behavior.
     * @param HandlerInterface $onHiddenToken This setting is responsible for
     *        the behavior of the lexer in case of detection of hidden/skipped
     *        tokens.
     *
     *        See {@see OnHiddenToken} for more details.
     *
     *        Note that you can also define your own {@see HandlerInterface} to
     *        override behavior.
     * @param HandlerInterface $onEndOfInput This setting is responsible for the
     *        operation of the terminal token ({@see EndOfInput}).
     *
     *        See also {@see OnEndOfInput} for more details.
     *
     *        Note that you can also define your own {@see HandlerInterface} to
     *        override behavior.
     * @param non-empty-string $unknown The identifier that marks each unknown
     *        token inside the executor (internal runtime). This parameter only
     *        needs to be changed if the name is already in use in the user's
     *        token set (in the {@see $tokens} parameter), otherwise it makes
     *        no sense.
     * @param non-empty-string $eoi
     */
    public function __construct(
        array $tokens = [],
        array $skip = [],
        DriverInterface $driver = null,
        ?HandlerInterface $onHiddenToken = null,
        ?HandlerInterface $onUnknownToken = null,
        ?HandlerInterface $onEndOfInput = null,
        string $unknown = Lexer::DEFAULT_UNKNOWN_TOKEN_NAME,
        string $eoi = Lexer::DEFAULT_EOI_TOKEN_NAME,
        ?SourceFactoryInterface $sources = null
    ) {
        $this->tokens = $tokens;
        $this->skip = $skip;

        $this->driver = $driver ?? new Markers(new MarkersCompiler(), $unknown);

        $this->eoi = $eoi;
        $this->unknown = $unknown;

        $this->onHiddenToken = $onHiddenToken ?? new NullHandler();
        $this->onUnknownToken = $onUnknownToken ?? new ThrowErrorHandler();
        $this->onEndOfInput = $onEndOfInput ?? new PassthroughHandler();

        $this->sources = $sources ?? new SourceFactory();
    }

    /**
     * @deprecated since phplrt 3.6 and will be removed in 4.0. Please use
     *             "$onUnknownToken" argument of the {@see __construct()}
     *             or {@see Lexer::withUnknownTokenHandler()} method instead.
     */
    public function disableUnrecognizedTokenException(): void
    {
        trigger_deprecation('phplrt/lexer', '3.6', <<<'MSG'
            Using "%s::disableUnrecognizedTokenException()" is deprecated.
            Please use %1$s::withUnknownTokenHandler() instead.
            MSG, static::class);

        $this->onUnknownToken = new PassthroughHandler();
    }

    /**
     * @param HandlerInterface $handler A handler that defines the behavior of
     *        the lexer in the case of a "hidden" token.
     *
     * @psalm-immutable This method returns a new {@see LexerInterface} instance
     *                  and does not change the current state of the lexer.
     * @api
     */
    public function withHiddenTokenHandler(HandlerInterface $handler): self
    {
        $self = clone $this;
        $self->onHiddenToken = $handler;

        return $self;
    }

    /**
     * @param HandlerInterface $handler A handler that defines the behavior of
     *        the lexer in the case of an "unknown" token.
     *
     * @psalm-immutable This method returns a new {@see LexerInterface} instance
     *                  and does not change the current state of the lexer.
     * @api
     */
    public function withUnknownTokenHandler(HandlerInterface $handler): self
    {
        $self = clone $this;
        $self->onUnknownToken = $handler;

        return $self;
    }

    /**
     * @param HandlerInterface $handler A handler that defines the behavior of
     *        the lexer in the case of an "end of input" token.
     *
     * @psalm-immutable This method returns a new {@see LexerInterface} instance
     *                  and does not change the current state of the lexer.
     * @api
     */
    public function withEndOfInputHandler(HandlerInterface $handler): self
    {
        $self = clone $this;
        $self->onEndOfInput = $handler;

        return $self;
    }

    /**
     * @deprecated since phplrt 3.6 and will be removed in 4.0.
     * @api
     */
    public function getDriver(): DriverInterface
    {
        trigger_deprecation('phplrt/lexer', '3.6', <<<'MSG'
            Using "%s::getDriver()" is deprecated.
            MSG, static::class);

        return $this->driver;
    }

    /**
     * @deprecated since phplrt 3.6 and will be removed in 4.0.
     * @api
     */
    public function setDriver(DriverInterface $driver): self
    {
        trigger_deprecation('phplrt/lexer', '3.6', <<<'MSG'
            Using "%s::setDriver(DriverInterface $driver)" is deprecated.
            MSG, static::class);

        $this->driver = $driver;

        return $this;
    }

    public function skip(string ...$tokens): self
    {
        $this->skip = \array_merge($this->skip, $tokens);

        return $this;
    }

    public function append(string $token, string $pattern): self
    {
        $this->reset();
        $this->tokens[$token] = $pattern;

        return $this;
    }

    private function reset(): void
    {
        $this->driver->reset();
    }

    public function appendMany(array $tokens): self
    {
        $this->reset();
        $this->tokens = \array_merge($this->tokens, $tokens);

        return $this;
    }

    public function prepend(string $token, string $pattern): self
    {
        $this->reset();
        $this->tokens = \array_merge([$token => $pattern], $this->tokens);

        return $this;
    }

    public function prependMany(array $tokens, bool $reverseOrder = false): self
    {
        $this->reset();
        $this->tokens = \array_merge($reverseOrder ? \array_reverse($tokens) : $tokens, $this->tokens);

        return $this;
    }

    public function remove(string ...$tokens): self
    {
        $this->reset();

        foreach ($tokens as $token) {
            unset($this->tokens[$token]);

            $index = \array_search($token, $this->skip, true);

            if ($index !== false) {
                unset($this->skip[$index]);
                $this->skip = \array_values($this->skip);
            }
        }

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
     */
    public function lex($source, int $offset = 0): iterable
    {
        try {
            $source = $this->sources->create($source);
        } catch (\Throwable $e) {
            throw LexerException::fromInternalError($e);
        }

        $unknown = [];
        $skip = array_flip($this->skip);

        try {
            foreach ($this->driver->run($this->tokens, $source, $offset) as $token) {
                // Process "hidden" tokens.
                if (\isset($skip[$token->getName()])) {
                    if (($handledToken = $this->handleHiddenToken($source, $token)) !== null) {
                        yield $handledToken;
                    }

                    continue;
                }

                if ($token->getName() === $this->unknown) {
                    $unknown[] = $token;
                    continue;
                }

                if ($unknown !== [] && ($result = $this->handleUnknownToken($source, $unknown))) {
                    yield $result;
                    $unknown = [];
                }

                yield $token;
            }
        } catch (SourceExceptionInterface $e) {
            throw LexerException::fromInternalError($e);
        }

        if ($unknown !== [] && $result = $this->handleUnknownToken($source, $unknown)) {
            yield $token = $result;
        }

        if (($eoi = $this->handleEoiToken($source, $token ?? null)) !== null) {
            yield $eoi;
        }
    }

    /**
     * @throws LexerRuntimeExceptionInterface
     */
    private function handleEoiToken(ReadableInterface $source, ?TokenInterface $last): ?TokenInterface
    {
        if (\in_array($this->eoi, $this->skip, true)) {
            return null;
        }

        $offset = $last !== null ? $last->getOffset() + $last->getBytes() : 0;
        $eoi = new EndOfInput($offset, $this->eoi);

        return $this->onEndOfInput->handle($source, $eoi);
    }

    /**
     * @param non-empty-list<TokenInterface> $tokens
     */
    private function reduceUnknownToken(array $tokens): TokenInterface
    {
        $concat = static function (string $data, TokenInterface $token): string {
            return $data . $token->getValue();
        };

        $value = \array_reduce($tokens, $concat, '');

        return new UnknownToken($value, \reset($tokens)->getOffset(), $this->unknown);
    }

    /**
     * @param non-empty-list<TokenInterface> $tokens
     *
     * @throws LexerRuntimeExceptionInterface
     */
    private function handleUnknownToken(ReadableInterface $source, array $tokens): ?TokenInterface
    {
        $token = $this->reduceUnknownToken($tokens);

        return $this->onUnknownToken->handle($source, $token);
    }

    /**
     * @throws LexerRuntimeExceptionInterface
     */
    private function handleHiddenToken(ReadableInterface $source, TokenInterface $token): ?TokenInterface
    {
        return $this->onHiddenToken->handle($source, $token);
    }
}
