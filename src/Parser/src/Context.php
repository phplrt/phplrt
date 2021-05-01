<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Buffer\BufferInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Grammar\RuleInterface;

/**
 * This is an internal implementation of parser mechanisms and modifying the
 * value of fields outside can disrupt the operation of parser's algorithms.
 *
 * The presence of public modifiers in fields is required only to speed up the
 * parser, since direct access is several times faster than using methods of
 * setting values or creating a new class at each step of the parser.
 *
 * @property-read ReadableInterface $source
 * @property-read BufferInterface $buffer
 *
 * @internal This is an internal implementation of parser
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
     *
     * @var TokenInterface|null
     */
    public ?TokenInterface $lastOrdinalToken = null;

    /**
     * Contains the token object which was last successfully processed
     * in the rules chain.
     *
     * Please note that this value contains the last token in time, and not
     * the last in order in the buffer, unlike the value of "$lastOrdinalToken".
     *
     * @var TokenInterface
     */
    public TokenInterface $lastProcessedToken;

    /**
     * Contains the NodeInterface object which was last successfully
     * processed while parsing.
     *
     * @var NodeInterface|null
     */
    public ?NodeInterface $node = null;

    /**
     * Contains the parser's current rule.
     *
     * @var RuleInterface|null
     */
    public ?RuleInterface $rule = null;

    /**
     * Contains the identifier of the current state of the parser.
     *
     * Note: This is a stateful data and may cause a race condition error. In
     * the future, it is necessary to delete this data with a replacement for
     * the stateless structure.
     *
     * @var int|string
     */
    public $state;

    /**
     * Contains information about the processed source.
     *
     * @var ReadableInterface
     */
    public ReadableInterface $source;

    /**
     * Contains a buffer of tokens that were collected from lexical analysis.
     *
     * @var BufferInterface
     */
    public BufferInterface $buffer;

    /**
     * @param BufferInterface $buffer
     * @param ReadableInterface $source
     * @param int|string $state
     * @param array $options
     */
    public function __construct(BufferInterface $buffer, ReadableInterface $source, $state, array $options)
    {
        $this->state = $state;
        $this->source = $source;
        $this->buffer = $buffer;
        $this->options = $options;

        $this->lastOrdinalToken = $this->lastProcessedToken = $this->buffer->current();
    }

    /**
     * {@inheritDoc}
     */
    public function getBuffer(): BufferInterface
    {
        return $this->buffer;
    }

    /**
     * {@inheritDoc}
     */
    public function getSource(): ReadableInterface
    {
        return $this->source;
    }

    /**
     * {@inheritDoc}
     */
    public function getNode(): ?NodeInterface
    {
        return $this->node;
    }

    /**
     * {@inheritDoc}
     */
    public function getRule(): RuleInterface
    {
        return $this->rule;
    }

    /**
     * {@inheritDoc}
     */
    public function getToken(): TokenInterface
    {
        return $this->lastProcessedToken;
    }

    /**
     * {@inheritDoc}
     */
    public function getState()
    {
        return $this->state;
    }
}
