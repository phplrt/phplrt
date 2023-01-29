<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Buffer\ArrayBuffer;
use Phplrt\Buffer\BufferInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\ParserConfigsInterface as Config;

/**
 * @mixin ParserConfigsInterface
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
     * @var class-string
     */
    private string $buffer = ArrayBuffer::class;

    /**
     * The initial state (initial rule identifier) of the parser.
     *
     * @var non-empty-string|int<0, max>|null
     */
    private $initial;

    /**
     * Token indicating the end of parsing.
     *
     * @var non-empty-string
     */
    private string $eoi = TokenInterface::END_OF_INPUT;

    /**
     * Possible tokens searching (enable if it is true)
     *
     * @var bool
     */
    private bool $possibleTokensSearching = false;

    /**
     * @var bool
     */
    private bool $useMutableBuffer = false;

    /**
     * Step reducer
     *
     * @var \Closure|null
     */
    private ?\Closure $step = null;

    /**
     * Sets an a token name indicating the end of parsing.
     *
     * @param non-empty-string $token
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
            ->startsAt($options[Config::CONFIG_INITIAL_RULE] ?? \array_key_first($this->rules))
            ->completeAt($options[Config::CONFIG_EOI] ?? $this->eoi)
            ->withBuffer($options[Config::CONFIG_BUFFER] ?? $this->buffer)
            ->buildUsing($options[Config::CONFIG_AST_BUILDER] ?? $this)
            ->eachStepThrough($options[Config::CONFIG_STEP_REDUCER] ?? null)
            ->possibleTokensSearching($options[Config::CONFIG_POSSIBLE_TOKENS_SEARCHING] ?? false)
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
     * Sets a tokens buffer class.
     *
     * @param class-string $class
     * @return $this
     */
    public function withBuffer(string $class): self
    {
        \assert(\is_subclass_of($class, BufferInterface::class));

        $this->buffer = $class;

        return $this;
    }

    /**
     * Sets an initial state (initial rule identifier) of the parser.
     *
     * @param non-empty-string|int<0, max> $initial
     * @return $this
     */
    public function startsAt($initial): self
    {
        $this->initial = $initial;

        return $this;
    }

    /**
     * Turn on/off for possible tokens searching
     *
     * @param bool $possibleTokensSearching
     * @return $this
     */
    public function possibleTokensSearching(bool $possibleTokensSearching): self
    {
        $this->possibleTokensSearching = $possibleTokensSearching;
        $this->useMutableBuffer = $this->possibleTokensSearching;

        return $this;
    }
}
