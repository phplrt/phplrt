<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer;

use Phplrt\Compiler\Lexer\Definition\RegexModifier;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Contracts\Lexer\ChannelInterface;

/**
 * Represents the result of building a lexer.
 */
final class LexerBuilderResult
{
    /**
     * List of global (non-namespaced) token definitions
     *
     * @var list<TokenDefinition>
     */
    public array $globals {
        get {
            if (isset($this->globals)) {
                return $this->globals;
            }

            $this->globals = [];

            foreach ($this->tokens as $definition) {
                if ($definition->namespace === null) {
                    $this->globals[] = $definition;
                }
            }

            return $this->globals;
        }
    }

    /**
     * List of token definitions grouped by namespace
     *
     * @var array<non-empty-string, list<TokenDefinition>>
     */
    public array $namespaces {
        get {
            if (isset($this->namespaces)) {
                return $this->namespaces;
            }

            $this->namespaces = [];

            foreach ($this->tokens as $definition) {
                $namespace = $definition->namespace;

                if ($namespace === null) {
                    continue;
                }

                $this->namespaces[$namespace][] = $definition;
            }

            return $this->namespaces;
        }
    }

    public function __construct(
        /**
         * @var list<TokenDefinition>
         */
        public readonly array $tokens,
        /**
         * @var list<RegexModifier>
         */
        public readonly array $flags,
        /**
         * @var list<ChannelInterface>
         */
        public readonly array $channels,
    ) {}

    /**
     * @return list<TokenDefinition>
     */
    public function getGlobalTokens(): array
    {
        $result = [];

        foreach ($this->tokens as $definition) {
            if ($definition->namespace === null) {
                $result[] = $definition;
            }
        }

        return $result;
    }
}
