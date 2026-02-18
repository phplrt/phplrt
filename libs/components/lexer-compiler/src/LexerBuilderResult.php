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
     * List of token definitions grouped by state
     *
     * @var array<non-empty-string, list<TokenDefinition>>
     */
    public array $states {
        get {
            if (isset($this->states)) {
                return $this->states;
            }

            $this->states = [];

            foreach ($this->tokens as $definition) {
                $state = $definition->namespace;

                if ($state === null) {
                    continue;
                }

                $this->states[$state][] = $definition;
            }

            foreach ($this->states as $state => $definitions) {
                $this->states[$state][] = $this->createUnknownToken();
            }

            return $this->states;
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
        return new RegexTokenDefinition('[^\\s]++')
            ->setChannel(Channel::Unknown);
    }
}
