<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceExceptionInterface;
use Phplrt\Lexer\Exception\UnexpectedStateException;
use Phplrt\Lexer\Exception\EndlessRecursionException;
use Phplrt\Source\File;

class Multistate implements LexerInterface
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

    /**
     * @param array<array-key, PositionalLexerInterface> $states
     * @param array<array-key, array<non-empty-string, array-key>> $transitions
     * @param array-key|null $state
     */
    public function __construct(array $states, array $transitions = [], $state = null)
    {
        foreach ($states as $name => $data) {
            $this->setState($name, $data);
        }

        $this->transitions = $transitions;
        $this->state = $state;
    }

    /**
     * @param array-key|null $state
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
     */
    public function when(string $token, $in, $then): self
    {
        $this->transitions[$in][$token] = $then;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $source
     * @param int<0, max> $offset
     *
     * @return iterable<TokenInterface>
     * @throws SourceExceptionInterface
     */
    public function lex($source, int $offset = 0): iterable
    {
        yield from $this->run(File::new($source), $offset);
    }

    /**
     * @param int<0, max> $offset
     *
     * @return iterable<TokenInterface>
     *
     * @throws RuntimeExceptionInterface
     * @psalm-suppress UnusedVariable
     * @psalm-suppress TypeDoesNotContainType
     */
    private function run(ReadableInterface $source, int $offset): iterable
    {
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

                if ($token->getName() === TokenInterface::END_OF_INPUT) {
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

                    $completed = false;

                    continue 2;
                }
            }
        } while (!$completed);
    }
}
