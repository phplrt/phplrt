<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Exception\UnexpectedStateException;
use Phplrt\Lexer\Exception\EndlessRecursionException;
use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;

/**
 * Class Multistate
 */
class Multistate implements LexerInterface
{
    /**
     * @var array|LexerInterface[]
     */
    private $states;

    /**
     * @var int|string
     */
    private $state;

    /**
     * @var array
     */
    private $transitions;

    /**
     * Multistate constructor.
     *
     * @param array|LexerInterface[] $states
     * @param array $transitions
     * @param int|string|null $state
     */
    public function __construct(array $states, array $transitions = [], $state = null)
    {
        $mapper = static function ($data) {
            return $data instanceof LexerInterface ? $data : new Lexer($data);
        };

        $this->states = \array_map($mapper, $states);

        $this->transitions = $transitions;
        $this->state = $state ?? \count($states) ? \array_key_first($states) : 0;
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
     * @param resource|string $source
     * @param int $offset
     * @return iterable|TokenInterface[]
     * @throws LexerExceptionInterface
     */
    public function lex($source, int $offset = 0): iterable
    {
        execution:

        /**
         * We save the offset for the state to prevent endless transitions
         * in the future.
         *
         * @noinspection IssetArgumentExistenceInspection
         */
        $states[$state ?? $state = $this->state] = $offset;

        /**
         * Checking the existence of the current state.
         */
        if (! isset($this->states[$state])) {
            /** @noinspection IssetArgumentExistenceInspection */
            throw new UnexpectedStateException($state, $token ?? null);
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
                    throw new EndlessRecursionException($state, $token ?? null);
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
    }
}
