<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Transformer;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Lexer\Builder\Analysis\LexerResultContext;
use Phplrt\Lexer\Builder\Compiler\LexerBuildingContext;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;

/**
 * Assembles the lexer out of the token definitions the compiler passes have
 * left behind.
 *
 * Identifiers are assigned here, so this is the point after which the token
 * definitions may no longer be rewritten.
 */
final readonly class LexerResultContextTransformer
{
    public function transform(LexerBuildingContext $context): LexerResultContext
    {
        /**
         * The very same "unknown" definition is shared between all states, so
         * that an unrecognized fragment is always reported using the same
         * token ID, no matter which state the lexer was in.
         */
        $unknown = $this->createUnknownToken();

        /** @var \SplObjectStorage<TokenDefinition, int> $identifiers */
        $identifiers = new \SplObjectStorage();

        $tokens = $this->index($identifiers, [...$context->tokens, $unknown]);

        $states = [];

        foreach ($context->states as $name => $state) {
            $states[$name] = $this->index($identifiers, [...$state, $unknown]);
        }

        return new LexerResultContext(
            tokens: $tokens,
            states: $states,
            flags: \array_values($context->flags),
            embeddedStates: $context->embeddedStates,
        );
    }

    /**
     * Assigns a globally unique identifier to each definition.
     *
     * A definition shared between several states keeps a single identifier.
     *
     * @param \SplObjectStorage<TokenDefinition, int> $identifiers
     * @param non-empty-list<TokenDefinition> $definitions
     * @return non-empty-array<int, TokenDefinition>
     */
    private function index(\SplObjectStorage $identifiers, array $definitions): array
    {
        $result = [];

        foreach ($definitions as $definition) {
            if (!isset($identifiers[$definition])) {
                $identifiers[$definition] = $identifiers->count();
            }

            $result[$identifiers[$definition]] = $definition;
        }

        return $result;
    }

    private function createUnknownToken(): TokenDefinition
    {
        return new RegexTokenDefinition('[^\\s]++')
            ->setChannel(Channel::Unknown);
    }
}
