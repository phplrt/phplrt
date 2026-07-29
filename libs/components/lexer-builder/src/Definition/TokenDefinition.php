<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Definition;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\UserDefinedChannel;

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
     * Contains what this token does to the reading, or {@see null} in case of
     * the token changes nothing
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

    public function hide(): self
    {
        return $this->setHidden();
    }

    public function show(): self
    {
        return $this->setHidden(false);
    }

    /**
     * @param non-empty-string $lexer
     */
    public function enter(string $lexer): self
    {
        return $this->setTransition(Transition::enter($lexer));
    }

    public function exit(): self
    {
        return $this->setTransition(Transition::exit());
    }

    public function stay(): self
    {
        return $this->setTransition(null);
    }

    /**
     * Updates the token name of the current definition and returns
     * itself as the fluent interface.
     *
     * @api
     *
     * @param non-empty-string|null $name
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
     * @api
     *
     * @param ChannelInterface|non-empty-string|null $channel
     * @return $this
     */
    public function setChannel(ChannelInterface|string|null $channel = null): self
    {
        $builtin = Channel::names();
        $channel ??= self::DEFAULT_TOKEN_CHANNEL;

        if (\is_string($channel)) {
            $channel = $builtin[$channel] ?? new UserDefinedChannel($channel);
        }

        $this->channel = $channel;

        return $this;
    }

    public function setTransition(?Transition $transition): self
    {
        $this->transition = $transition;

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
