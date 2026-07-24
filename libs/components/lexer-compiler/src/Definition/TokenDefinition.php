<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Definition;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;

/**
 * @phpstan-sealed RegexTokenDefinition|ValueTokenDefinition
 */
abstract class TokenDefinition extends Definition
{
    private const ChannelInterface DEFAULT_TOKEN_CHANNEL = Channel::DEFAULT;

    /**
     * Contains token name, or {@see null} in case of token is anonymous
     *
     * @var non-empty-string|null
     */
    public ?string $name;

    /**
     * Contains {@see true} in case of token should be
     * hidden, or {@see false} instead
     */
    public bool $isHidden {
        get => $this->channel === Channel::Hidden;
        set(bool $isHidden) {
            $this->channel = $isHidden ? Channel::Hidden : self::DEFAULT_TOKEN_CHANNEL;
        }
    }

    /**
     * Contains optional channel reference
     */
    public ChannelInterface $channel = self::DEFAULT_TOKEN_CHANNEL;

    /**
     * Contains the lexer state change triggered by this token, or {@see null}
     * in case of the token does not affect the lexer state
     */
    public private(set) ?Transition $transition = null;

    /**
     * @param non-empty-string|null $name
     */
    public function __construct(
        ?string $name = null,
    ) {
        $this->name = $name;
    }

    /**
     * Updates the token name of the current definition and returns
     * itself as the fluent interface.
     *
     * @api
     *
     * @param non-empty-string|null $name
     *
     * @return $this
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @api
     *
     * @return $this
     */
    public function setAnonymous(): self
    {
        return $this->setName(null);
    }

    /**
     * @api
     *
     * @return $this
     */
    public function setHidden(bool $hidden = true): self
    {
        $this->isHidden = $hidden;

        return $this;
    }

    /**
     * @param non-empty-string $name
     */
    private function createCustomChannel(string $name): ChannelInterface
    {
        return new class ($name) implements ChannelInterface {
            public function __construct(
                /**
                 * @var non-empty-string
                 */
                public string $value,
            ) {}
        };
    }

    /**
     * @api
     *
     * @param ChannelInterface|non-empty-string|null $channel
     *
     * @return $this
     */
    public function setChannel(ChannelInterface|string|null $channel = null): self
    {
        $channel ??= self::DEFAULT_TOKEN_CHANNEL;

        if (\is_string($channel)) {
            $channel = Channel::tryFrom($channel)
                ?? $this->createCustomChannel($channel);
        }

        $this->channel = $channel;

        return $this;
    }

    /**
     * Makes the token push the given state onto the lexer's state stack.
     *
     * @api
     *
     * @param non-empty-string $state
     *
     * @return $this
     */
    public function enter(string $state): self
    {
        $this->transition = Transition::enter($state);

        return $this;
    }

    /**
     * Makes the token pop the topmost state from the lexer's state stack.
     *
     * @api
     *
     * @return $this
     */
    public function exit(): self
    {
        $this->transition = Transition::exit();

        return $this;
    }

    /**
     * Makes the token keep the lexer in its current state.
     *
     * @api
     *
     * @return $this
     */
    public function stay(): self
    {
        $this->transition = null;

        return $this;
    }

    /**
     * @return non-empty-string
     */
    abstract protected function printValue(): string;

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        if ($this->name === null) {
            return $this->printValue();
        }

        return \sprintf('%s (%s)', $this->printValue(), $this->name);
    }
}
