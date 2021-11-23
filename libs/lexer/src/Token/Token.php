<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\ChannelInterface;

class Token extends BaseToken
{
    /**
     * @var non-empty-string
     */
    public const NAME_EOI = 'T_EOI';

    /**
     * @var non-empty-string
     */
    public const NAME_UNKNOWN = 'T_UNKNOWN';

    /**
     * @param non-empty-string|int $name
     * @param string $value
     * @param positive-int|0 $offset
     * @param ChannelInterface $channel
     */
    public function __construct(
        public string|int $name,
        public string $value,
        public int $offset = 0,
        public ChannelInterface $channel = Channel::DEFAULT
    ) {
        assert($this->name !== '', new \InvalidArgumentException(
            'Token name MUST not be empty'
        ));

        assert($this->offset >= 0, new \InvalidArgumentException(
            'Token offset MUST be greater or equals than 0, but ' . $this->offset . ' passed'
        ));
    }

    /**
     * @param non-empty-string|int $name
     * @param string $value
     * @param positive-int|0 $offset
     * @param ChannelInterface $channel
     * @return static
     */
    public static function new(
        string|int $name,
        string $value,
        int $offset = 0,
        ChannelInterface $channel = Channel::GENERAL
    ): self {
        return new self($name, $value, $offset, $channel);
    }

    /**
     * @param positive-int|0 $offset
     * @param non-empty-string $name
     * @param ChannelInterface $channel
     * @return static
     */
    public static function eoi(
        int $offset = 0,
        string $name = self::NAME_EOI,
        ChannelInterface $channel = Channel::END_OF_INPUT
    ): self {
        return new self($name, "\0", $offset, $channel);
    }

    /**
     * @param string $value
     * @param positive-int|0 $offset
     * @param non-empty-string $name
     * @param ChannelInterface $channel
     * @return static
     */
    public static function unknown(
        string $value,
        int $offset = 0,
        string $name = self::NAME_UNKNOWN,
        ChannelInterface $channel = Channel::UNKNOWN
    ): self {
        return new self($name, $value, $offset, $channel);
    }

    /**
     * @param non-empty-string|int $name
     * @param string $value
     * @param positive-int|0 $offset
     * @param ChannelInterface $channel
     * @return static
     */
    public static function skip(
        string|int $name,
        string $value,
        int $offset = 0,
        ChannelInterface $channel = Channel::HIDDEN
    ): self {
        return new self($name, $value, $offset, $channel);
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string|int
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * {@inheritDoc}
     */
    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }
}
