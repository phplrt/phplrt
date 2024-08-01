<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Buffer\ArrayBuffer;
use Phplrt\Buffer\BufferInterface;
use Phplrt\Lexer\Token\EndOfInput;
use Phplrt\Parser\ParserConfigsInterface as Config;

/**
 * @mixin ParserConfigsInterface
 */
trait ParserConfigsTrait
{
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
     * Token indicating the end of parsing.
     *
     * @var non-empty-string
     */
    private string $eoi = EndOfInput::DEFAULT_TOKEN_NAME;

    /**
     * Enables support for trailing tokens after a completed grammar.
     */
    private bool $allowTrailingTokens = false;

    /**
     * Step reducer.
     *
     * @var (\Closure(Context, (\Closure(): mixed)): mixed)|null
     */
    private ?\Closure $step = null;

    /**
     * Initialize parser's configuration options.
     */
    protected function bootParserConfigsTrait(array $options): void
    {
        $this->completeAt($options[Config::CONFIG_EOI] ?? $this->eoi)
            ->withBuffer($options[Config::CONFIG_BUFFER] ?? $this->buffer)
            ->eachStepThrough($options[Config::CONFIG_STEP_REDUCER] ?? null)
            ->allowTrailingTokens($options[Config::CONFIG_ALLOW_TRAILING_TOKENS] ?? false);
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
     * @param (callable(Context, (\Closure(): mixed)): mixed)|null $step
     */
    public function eachStepThrough(?callable $step): self
    {
        $this->step = $step === null ? null : \Closure::fromCallable($step);

        return $this;
    }

    /**
     * Sets a tokens buffer class.
     *
     * @param class-string<BufferInterface> $class
     */
    public function withBuffer(string $class): self
    {
        \assert(\is_subclass_of($class, BufferInterface::class));

        $this->buffer = $class;

        return $this;
    }

    public function allowTrailingTokens(bool $allow = true): self
    {
        $this->allowTrailingTokens = $allow;

        return $this;
    }
}
