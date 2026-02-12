<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer;

use Phplrt\Compiler\Lexer\Definition\RegexModifier;
use Phplrt\Compiler\Lexer\Definition\RegexTokenDefinition;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Contracts\Lexer\Channel;
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
    public array $global {
        get {
            if (isset($this->global)) {
                return $this->global;
            }

            $this->global = [];

            foreach ($this->tokens as $definition) {
                if ($definition->namespace === null) {
                    $this->global[] = $definition;
                }
            }

            $this->global[] = $this->createUnknownToken();

            return $this->global;
        }
    }

    /**
     * List of token definitions grouped by namespace
     *
     * @var array<non-empty-string, list<TokenDefinition>>
     */
    public array $namespaced {
        get {
            if (isset($this->namespaced)) {
                return $this->namespaced;
            }

            $this->namespaced = [];

            foreach ($this->tokens as $definition) {
                $namespace = $definition->namespace;

                if ($namespace === null) {
                    continue;
                }

                $this->namespaced[$namespace][] = $definition;
            }

            foreach ($this->namespaced as $namespace => $definitions) {
                $this->namespaced[$namespace][] = $this->createUnknownToken();
            }

            return $this->namespaced;
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

    private function createUnknownToken(): TokenDefinition
    {
        return new RegexTokenDefinition('.+?')
            ->setChannel(Channel::Unknown);
    }
}
