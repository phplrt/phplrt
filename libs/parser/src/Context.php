<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Context\ContextOptionsTrait;
use Phplrt\Parser\Grammar\RuleInterface;

/**
 * This is an internal implementation of parser mechanisms and modifying the
 * value of fields outside can disrupt the operation of parser's algorithms.
 *
 * The presence of public modifiers in fields is required only to speed up the
 * parser, since direct access is several times faster than using methods of
 * setting values or creating a new class at each step of the parser.
 *
 * @final marked as final since phplrt 3.4 and will be final since 4.0
 */
class Context implements ContextInterface
{
    use ContextOptionsTrait;

    /**
     * Contains the most recent token object in the token list
     * (buffer) which was last successfully processed in the rules chain.
     *
     * It is required so that in case of errors it is possible to report that
     * it was on it that the problem arose.
     *
     * Please note that this value contains the last in the list of processed
     * ones, and not the last in time that was processed.
     */
    public ?TokenInterface $lastOrdinalToken = null;

    /**
     * Contains the token object which was last successfully processed
     * in the rules chain.
     *
     * Please note that this value contains the last token in time, and not
     * the last in order in the buffer, unlike the value of "$lastOrdinalToken".
     */
    public TokenInterface $lastProcessedToken;

    /**
     * Contains the node object which was last successfully
     * processed while parsing.
     */
    public ?object $node = null;

    /**
     * Contains the parser's current rule.
     */
    public ?RuleInterface $rule = null;

    /**
     * Contains the identifier of the current state of the parser.
     *
     * Note: This is a stateful data and may cause a race condition error. In
     * the future, it is necessary to delete this data with a replacement for
     * the stateless structure.
     *
     * @var array-key
     */
    public string|int $state;

    /**
     * @param array-key $state
     * @param array<non-empty-string, mixed> $options
     */
    public function __construct(
        /**
         * Contains a buffer of tokens that were collected from lexical analysis.
         */
        public readonly BufferInterface $buffer,
        /**
         * Contains information about the processed source.
         */
        public readonly ReadableInterface $source,
        int|string $state,
        array $options
    ) {
        $this->state = $state;
        $this->options = $options;

        $this->lastOrdinalToken = $this->lastProcessedToken = $this->buffer->current();
    }

    public function getBuffer(): BufferInterface
    {
        return $this->buffer;
    }

    public function getSource(): ReadableInterface
    {
        return $this->source;
    }

    public function getNode(): ?object
    {
        return $this->node;
    }

    public function getRule(): ?RuleInterface
    {
        return $this->rule;
    }

    public function getToken(): TokenInterface
    {
        return $this->lastProcessedToken;
    }

    public function getState(): int|string
    {
        return $this->state;
    }
}
