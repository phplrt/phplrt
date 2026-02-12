<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Regex;

use Phplrt\Compiler\Lexer\Definition\AliasedDefinition;

final class RegexGeneratorResult implements \Stringable
{
    /**
     * @var array<non-empty-string, non-empty-string>
     */
    public array $channels {
        get {
            if (isset($this->channels)) {
                return $this->channels;
            }

            $this->channels = [];

            foreach ($this->tokens as $aliased) {
                $channel = $aliased->definition->channel;

                if ($channel === null) {
                    continue;
                }

                $this->channels[$aliased->alias] = $channel->value;
            }

            return $this->channels;
        }
    }

    /**
     * @var array<non-empty-string, non-empty-string|int>
     */
    public array $aliases {
        get {
            if (isset($this->aliases)) {
                return $this->aliases;
            }

            $this->aliases = [];
            $index = 0;

            foreach ($this->tokens as $aliased) {
                $name = $aliased->definition->name;

                if ($name === null) {
                    $this->aliases[$aliased->alias] = $index++;

                    continue;
                }

                $this->aliases[$aliased->alias] = $name;
            }

            return $this->aliases;
        }
    }

    public function __construct(
        /**
         * @var non-empty-string
         */
        public readonly string $pattern,
        /**
         * @var list<AliasedDefinition>
         */
        public readonly array $tokens,
    ) {}

    public function __toString(): string
    {
        return $this->pattern;
    }
}
