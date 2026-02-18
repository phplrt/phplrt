<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Regex;

use Phplrt\Compiler\Lexer\Definition\TokenDefinition;

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

                if ($channel === null) {
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

    public function __construct(
        /**
         * @var non-empty-string
         */
        public readonly string $pattern,
        /**
         * @var list<TokenDefinition>
         */
        public readonly array $tokens,
    ) {}

    public function __toString(): string
    {
        return $this->pattern;
    }
}
