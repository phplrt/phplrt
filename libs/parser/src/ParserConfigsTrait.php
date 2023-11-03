<?php

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
     * @var array-key|null
     */
    private $initial;

    /**
     * Token indicating the end of parsing.
     *
     * @var non-empty-string
     */
    private string $eoi = TokenInterface::END_OF_INPUT;

    /**
     * Possible tokens searching (enable if it is {@see true})
     */
    private bool $possibleTokensSearching = false;

    private bool $useMutableBuffer = false;

    private bool $allowTrailingTokens = false;

    /**
     * Step reducer.
     */
    private ?\Closure $step = null;

    /**
     * Initialize parser's configuration options.
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
            ->allowTrailingTokens($options[Config::CONFIG_ALLOW_TRAILING_TOKENS] ?? false)
        ;
    }

    /**
     * Sets a token name indicating the end of parsing.
     *
     * @param non-empty-string $token
     */
    public function completeAt(string $token): self
    {
        $this->eoi = $token;

        return $this;


    /**
     * Sets an abstract syntax tree builder.
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
     * @param array-key $initial
     */
    public function startsAt($initial): self
    {
        $this->initial = $initial;

        return $this;
    }

    /**
     * Turn on/off for possible tokens searching
     */
    public function possibleTokensSearching(bool $possibleTokensSearching): self
    {
        $this->possibleTokensSearching = $possibleTokensSearching;
        $this->useMutableBuffer = $this->possibleTokensSearching;

        return $this;
    }

    public function allowTrailingTokens(bool $allow = true): self
    {
        $this->allowTrailingTokens = $allow;

        return $this;
    }
}
