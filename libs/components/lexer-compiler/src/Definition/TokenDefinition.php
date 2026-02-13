<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Definition;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;

/**
 * @phpstan-sealed RegexTokenDefinition|ValueTokenDefinition
 */
abstract class TokenDefinition implements \Stringable
{
    /**
     * Contains token state, or {@see null} in case of token is global.
     *
     * @var non-empty-string|null
     */
    public ?string $state = null;

    /**
     * Contains token name, or {@see null} in case of token is anonymous
     *
     * @var non-empty-string|null
     */
    public ?string $name;

    /**
     * Gets the fully qualified token name
     *
     * @var non-empty-string|null
     */
    public ?string $fqn {
        get {
            if ($this->name === null) {
                return null;
            }

            $name = $this->name;

            if ($this->state !== null) {
                $name = $this->state . ':' . $name;
            }

            return $name;
        }
    }

    /**
     * Contains {@see true} in case of token should be
     * hidden, or {@see false} instead
     */
    public bool $isHidden {
        get => $this->channel === Channel::Hidden;
        set(bool $isHidden) {
            $this->channel = $isHidden ? Channel::Hidden : null;
        }
    }

    /**
     * Contains optional channel reference
     */
    public ?ChannelInterface $channel = null;

    /**
     * Contains transition namespace
     *
     * @var TransitionType|non-empty-string|null
     */
    public TransitionType|string|null $transition = null;

    /**
     * @param non-empty-string|null $name
     */
    public function __construct(
        ?string $name = null,
    ) {
        $this->name = $name;
    }

    /**
     * @api
     * @param non-empty-string|null $namespace
     * @return $this
     */
    public function setTransition(?string $namespace): self
    {
        $this->transition = $namespace;

        return $this;
    }

    /**
     * @api
     * @return $this
     */
    public function setGlobalTransition(): self
    {
        $this->transition = TransitionType::Exit;

        return $this;
    }

    /**
     * Updates the token namespace of the current definition and returns
     * itself as the fluent interface.
     *
     * @api
     *
     * @param non-empty-string|null $state
     *
     * @return $this
     */
    public function setState(?string $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Removes token namespace
     *
     * @api
     *
     * @return $this
     */
    public function setGlobalNamespace(): self
    {
        $this->state = null;

        return $this;
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
     * @api
     *
     * @return $this
     */
    public function setChannel(?ChannelInterface $channel): self
    {
        $this->channel = $channel;

        return $this;
    }
}
