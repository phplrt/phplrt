<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Definition;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\UserDefinedChannel;

/**
 * @property bool $isHidden Contains {@see true} in case of token should be
 *           hidden, or {@see false} instead.
 *
 *           Note: Starting with PHP 8.4, in the future, this annotation will
 *           be expressed as a full-fledged property.
 *
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
     * Contains optional channel reference
     */
    public ChannelInterface $channel = self::DEFAULT_TOKEN_CHANNEL;

    /**
     * Contains what this token does to the reading, or {@see null} in case of
     * the token changes nothing
     *
     * @phpstan-readonly-allow-private-mutation
     */
    public ?Transition $transition = null;

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
        $this->channel = $hidden ? Channel::Hidden : self::DEFAULT_TOKEN_CHANNEL;

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

    public function __get(string $property): mixed
    {
        return match ($property) {
            'isHidden' => $this->channel === Channel::Hidden,
            default => throw new \Error(\sprintf('Undefined property %s::$%s', static::class, $property)),
        };
    }

    public function __set(string $property, mixed $value): void
    {
        switch ($property) {
            case 'isHidden':
                $this->setHidden((bool) $value);
                break;

            default:
                throw new \Error(\sprintf('Undefined property %s::$%s', static::class, $property));
        }
    }
}
