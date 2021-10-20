<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Config;

use Phplrt\Buffer\Factory;
use Phplrt\Contracts\Buffer\BufferInterface;
use Phplrt\Contracts\Buffer\FactoryInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\BuilderInterface;

/**
 * @psalm-import-type StepReducer from ConfigInterface
 * @psalm-type ConfigArray = array {
 *  initial?:       string|int|null,
 *  builder?:       BuilderInterface|null,
 *  buffer?:        FactoryInterface|null,
 *  buffer_size?:   int|null,
 *  eoi?:           string|null,
 *  step?:          StepReducer|null,
 * }
 */
final class Config implements ConfigInterface
{
    /**
     * An abstract syntax tree builder.
     *
     * @var BuilderInterface|null
     */
    private ?BuilderInterface $builder = null;

    /**
     * A buffer class that allows you to iterate over the stream of tokens and
     * return to the selected position.
     *
     * Initialized by the generator with tokens during parser launch.
     *
     * @var FactoryInterface
     */
    private FactoryInterface $buffer;

    /**
     * buffer size.
     *
     * @var int|null
     */
    private ?int $bufferSize = null;

    /**
     * The initial state (initial rule identifier) of the parser.
     *
     * @var string|int|null
     */
    private $initial;

    /**
     * Token indicating the end of parsing.
     *
     * @var string
     */
    private string $eoi = TokenInterface::END_OF_INPUT;

    /**
     * Step reducer
     *
     * @var \Closure|null
     */
    private ?\Closure $step = null;

    /**
     * @param ConfigArray $config
     */
    public function __construct(array $config)
    {
        $this->builder = $config[Options::CONFIG_AST_BUILDER] ?? null;
        $this->buffer = $config[Options::CONFIG_BUFFER] ?? new Factory();
        $this->bufferSize = $config[Options::CONFIG_BUFFER_SIZE] ?? null;
        $this->initial = $config[Options::CONFIG_INITIAL_RULE] ?? null;
        $this->eoi = $config[Options::CONFIG_EOI] ?? $this->eoi;
        $this->step = $config[Options::CONFIG_STEP_REDUCER] = $this->step;
    }

    /**
     * {@inheritDoc}
     */
    public function getInitialRule(array $rules)
    {
        if ($this->initial !== null) {
            return $this->initial;
        }

        foreach ($rules as $name => $_) {
            if (\is_string($name)) {
                return $name;
            }
        }

        return \array_key_first($rules);
    }

    /**
     * {@inheritDoc}
     */
    public function getBuffer(iterable $tokens): BufferInterface
    {
        return $this->buffer->create($tokens, $this->bufferSize);
    }

    /**
     * {@inheritDoc}
     */
    public function getStepReducer(): ?callable
    {
        return $this->step;
    }

    /**
     * {@inheritDoc}
     */
    public function getEoiTokenName(): string
    {
        return $this->eoi;
    }

    /**
     * {@inheritDoc}
     */
    public function getBuilder(): BuilderInterface
    {
        return $this->builder;
    }

    /**
     * Sets an a token name indicating the end of parsing.
     *
     * @param string $token
     * @return $this
     */
    public function completeAt(string $token): self
    {
        $self = clone $this;
        $self->eoi = $token;

        return $self;
    }

    /**
     * Sets an abstract syntax tree builder.
     *
     * @param BuilderInterface $builder
     * @return $this
     */
    public function buildUsing(BuilderInterface $builder): self
    {
        $self = clone $this;
        $self->builder = $builder;

        return $self;
    }

    /**
     * Allows to add an interceptor to each step of the parser. May be required
     * for debugging.
     *
     * <code>
     *  $parser->eachStepThrough(function (Context $ctx, \Closure $next) {
     *      echo $ctx->getState() . ':' . $ctx->getToken() . "\n";
     *
     *      return $next($ctx);
     *  });
     * </code>
     *
     * @param callable|null $step
     * @return $this
     */
    public function eachStepThrough(?callable $step): self
    {
        $self = clone $this;
        $self->step = $step === null ? null : \Closure::fromCallable($step);

        return $self;
    }

    /**
     * Sets a tokens buffer factory.
     *
     * @param FactoryInterface $buffer
     * @return $this
     */
    public function withBuffer(FactoryInterface $buffer): self
    {
        $self = clone $this;
        $self->buffer = $buffer;

        return $self;
    }

    /**
     * Sets a tokens buffer size.
     *
     * @param int|null $size
     * @return $this
     */
    public function withBufferSize(int $size = null): self
    {
        $self = clone $this;
        $self->bufferSize = $size;

        return $self;
    }

    /**
     * Sets an initial state (initial rule identifier) of the parser.
     *
     * @param string|int|null $initial
     * @return $this
     */
    public function startsAt($initial): self
    {
        assert(\is_string($initial) || \is_int($initial) || $initial === null);

        $self = clone $this;
        $self->initial = $initial;

        return $self;
    }
}
