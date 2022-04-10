<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Exception\UnexpectedStateException;
use Phplrt\Lexer\Exception\EndlessRecursionException;
use Phplrt\Source\File;

class Multistate implements LexerInterface
{
    /**
     * @var array|LexerInterface[]
     */
    private array $states = [];

    /**
     * @var int|string|null
     */
    private $state;

    /**
     * @var array
     */
    private array $transitions;

    /**
     * @param array|LexerInterface[] $states
     * @param array $transitions
     * @param int|string|null $state
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
     * @param string|int|null $state
     * @return $this
     */
    public function startsWith($state): self
    {
        assert(\is_string($state) || \is_int($state) || $state === null); /** @phpstan-ignore-line */

        $this->state = $state;

        return $this;
    }

    /**
     * @param string|int $name
     * @param array|LexerInterface $data
     * @return $this
     */
    public function setState($name, $data): self
    {
        assert(\is_string($name) || \is_int($name)); /** @phpstan-ignore-line */
        assert(\is_array($data) || $data instanceof LexerInterface); /** @phpstan-ignore-line */

        $this->states[$name] = $data instanceof LexerInterface ? $data : new Lexer($data);

        return $this;
    }

    /**
     * @param string|int $name
     * @return $this
     */
    public function removeState($name): self
    {
        unset($this->states[$name]);

        return $this;
    }

    /**
     * @param string $token
     * @param int|string $in
     * @param int|string $then
     * @return Multistate|$this
     */
    public function when(string $token, $in, $then): self
    {
        $this->transitions[$in][$token] = $then;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function lex($source, int $offset = 0): iterable
    {
        $this->boot($source = File::new($source));

        yield from $this->run($source, $offset);
    }

    /**
     * @param ReadableInterface $source
     * @return void
     */
    private function boot(ReadableInterface $source): void
    {
        if (\count($this->states) === 0) {
            throw UnexpectedStateException::fromEmptyStates($source);
        }

        if ($this->state === null) {
            $this->state = \array_key_first($this->states);
        }
    }

    /**
     * @param ReadableInterface $source
     * @param int $offset
     * @return \Generator
     * @throws RuntimeExceptionInterface
     * @psalm-suppress UnusedVariable
     */
    private function run(ReadableInterface $source, int $offset): \Generator
    {
        $state = null; // PHPStan bugfix

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
            if (! isset($this->states[$state])) {
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
                        throw EndlessRecursionException::fromState($state, $source, $token ?? null);
                    }

                    $completed = false;

                    continue 2;
                }
            }
        } while (! $completed);
    }
}
