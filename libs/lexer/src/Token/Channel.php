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

enum Channel: string implements ChannelInterface
{
    /**
     * General token's type matches any non-special token.
     */
    case GENERAL = 'GENERAL';

    /**
     * Hidden tokens channel name.
     */
    case HIDDEN = 'HIDDEN';

    /**
     * This token's type matches any unrecognized token.
     */
    case UNKNOWN = 'UNKNOWN';

    /**
     * This token's type corresponds to a terminal token and can only be
     * singular in the entire token stream.
     */
    case END_OF_INPUT = 'END_OF_INPUT';

    /**
     * Default channel for all tokens.
     * @var ChannelInterface
     */
    public const DEFAULT = self::GENERAL;

    /**
     * @param non-empty-string $channel
     * @return ChannelInterface
     *
     * @psalm-suppress all Psalm bug (enum methods may not be found).
     */
    public static function create(string $channel): ChannelInterface
    {
        static $index = [];

        if (($result = self::tryFrom($channel)) !== null) {
            return $result;
        }

        assert(\trim($channel) !== '', new \InvalidArgumentException(
            'Channel name MUST not be empty'
        ));

        if (isset($index[$channel])) {
            return $index[$channel];
        }

        return $index[$channel] = new class ($channel) implements ChannelInterface {
            /**
             * @param non-empty-string $name
             */
            public function __construct(
                private readonly string $name,
            ) {
            }

            /**
             * {@inheritDoc}
             */
            public function getName(): string
            {
                return $this->name;
            }
        };
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }
    }
