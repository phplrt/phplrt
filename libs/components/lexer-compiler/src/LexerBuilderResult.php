<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer;

use Phplrt\Compiler\Lexer\Definition\RegexModifier;
use Phplrt\Compiler\Lexer\Definition\RegexTokenDefinition;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Contracts\Lexer\Channel;

/**
 * Represents the result of building a lexer.
 */
final class LexerBuilderResult
{
    /**
     * List of token definitions grouped by state
     *
     * @var array<non-empty-string, non-empty-list<TokenDefinition>>
     */
    public private(set) array $states {
        /**
         * @param array<non-empty-string, list<TokenDefinition>> $states
         */
        set(array $states) {
            foreach ($states as $name => $tokens) {
                $tokens[] = $this->createUnknownToken();

                $states[$name] = $tokens;
            }

            $this->states = $states;
        }
    }

    /**
     * List of global (non-namespaced) token definitions
     *
     * @var non-empty-list<TokenDefinition>
     */
    public private(set) array $tokens {
        /**
         * @param list<TokenDefinition> $tokens
         */
        set(array $tokens) {
            $tokens[] = $this->createUnknownToken();

            $this->tokens = $tokens;
        }
    }

    /**
     * @param list<TokenDefinition> $tokens
     * @param array<non-empty-string, list<TokenDefinition>> $states
     */
    public function __construct(
        array $tokens,
        array $states,
        /**
         * @var list<RegexModifier>
         */
        public readonly array $flags,
    ) {
        $this->tokens = $tokens;
        $this->states = $states;
    }

    private function createUnknownToken(): TokenDefinition
    {
        return new RegexTokenDefinition('[^\\s]++')
            ->setChannel(Channel::Unknown);
    }
}
