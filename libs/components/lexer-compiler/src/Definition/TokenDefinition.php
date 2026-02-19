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
    public ?string $namespace = null;

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

            if ($this->namespace !== null) {
                $name = $this->namespace . ':' . $name;
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
     * @api
     *
     * @return $this
     */
    public function setChannel(?ChannelInterface $channel): self
    {
        $this->channel = $channel;

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
        return \vsprintf('%s (%s)', [
            $this->printValue(),
            $this->name ?? '*anonymous*',
        ]);
    }
}
