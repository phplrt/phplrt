<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Buffer\Factory;
use Phplrt\Contracts\Buffer\FactoryInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * @mixin ConfigInterface
 */
trait ParserConfigsTrait
{
    /**
     * An abstract syntax tree builder.
     *
     * @var BuilderInterface
     */
    private BuilderInterface $builder;

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
     * Buffer size.
     *
     * @var positive-int|null
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
     * Sets an a token name indicating the end of parsing.
     *
     * @param string $token
     * @return $this
     */
    public function completeAt(string $token): self
    {
        $this->eoi = $token;

        return $this;
    }

    /**
     * Initialize parser's configuration options
     *
     * @param array $options
     * @return void
     */
    protected function bootParserConfigsTrait(array $options): void
    {
        $this
            ->startsAt($options[ConfigInterface::CONFIG_INITIAL_RULE] ?? \array_key_first($this->rules))
            ->completeAt($options[ConfigInterface::CONFIG_EOI] ?? $this->eoi)
            ->withBuffer($options[ConfigInterface::CONFIG_BUFFER] ?? new Factory())
            ->withBufferSize($options[ConfigInterface::CONFIG_BUFFER_SIZE] ?? $this->bufferSize)
            ->buildUsing($options[ConfigInterface::CONFIG_AST_BUILDER] ?? $this)
            ->eachStepThrough($options[ConfigInterface::CONFIG_STEP_REDUCER] ?? null)
        ;
    }

    /**
     * Sets an abstract syntax tree builder.
     *
     * @param BuilderInterface $builder
     * @return $this
     */
    public function buildUsing(BuilderInterface $builder): self
    {
        $this->builder = $builder;

        return $this;
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
        $this->step = $step === null ? null : \Closure::fromCallable($step);

        return $this;
    }

    /**
     * Sets a tokens buffer factory.
     *
     * @param FactoryInterface $buffer
     * @return $this
     */
    public function withBuffer(FactoryInterface $buffer): self
    {
        $this->buffer = $buffer;

        return $this;
    }

    /**
     * Sets a tokens buffer size.
     *
     * @param int|null $size
     * @return $this
     */
    public function withBufferSize(int $size = null): self
    {
        $this->bufferSize = $size;

        return $this;
    }

    /**
     * Sets an initial state (initial rule identifier) of the parser.
     *
     * @param mixed $initial
     * @return $this
     */
    public function startsAt($initial): self
    {
        $this->initial = $initial;

        return $this;
    }
}
