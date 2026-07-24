<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Regex;

use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\Definition\TransitionType;
use Phplrt\Contracts\Lexer\Channel;

final class RegexGeneratorResult implements \Stringable
{
    /**
     * Gets a map of token ID and its channel
     *
     * @var array<int, non-empty-string>
     */
    public array $channels {
        get {
            if (isset($this->channels)) {
                return $this->channels;
            }

            $this->channels = [];

            foreach ($this->tokens as $id => $token) {
                $channel = $token->channel;

                if ($channel === Channel::DEFAULT) {
                    continue;
                }

                $this->channels[$id] = $channel->value;
            }

            return $this->channels;
        }
    }

    /**
     * Gets a map of token ID and its original name
     *
     * @var array<int, non-empty-string>
     */
    public array $names {
        get {
            if (isset($this->names)) {
                return $this->names;
            }

            $this->names = [];

            foreach ($this->tokens as $id => $token) {
                if ($token->name === null) {
                    continue;
                }

                $this->names[$id] = $token->name;
            }

            return $this->names;
        }
    }

    /**
     * Gets a map of token ID and the state transition it triggers.
     *
     * A {@see string} value enters the named state, while a {@see null} one
     * leaves the current state.
     *
     * @var array<int, non-empty-string|null>
     */
    public array $transitions {
        get {
            if (isset($this->transitions)) {
                return $this->transitions;
            }

            $this->transitions = [];

            foreach ($this->tokens as $id => $token) {
                $transition = $token->transition;

                if ($transition === null) {
                    continue;
                }

                $this->transitions[$id] = $transition->type === TransitionType::Enter
                    ? $transition->state
                    : null;
            }

            return $this->transitions;
        }
    }

    public function __construct(
        /**
         * @var non-empty-string
         */
        public readonly string $pattern,
        /**
         * @var array<int, TokenDefinition>
         */
        public readonly array $tokens,
    ) {}

    public function __toString(): string
    {
        return $this->pattern;
    }
}
