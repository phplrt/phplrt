<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer;

use Phplrt\Compiler\Lexer\Definition\RegexModifier;
use Phplrt\Compiler\Lexer\Definition\RegexTokenDefinition;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Contracts\Lexer\Channel;

/**
 * Represents the result of building a lexer.
 *
 * Token identifiers are unique across ALL states, so a token ID is always
 * enough to distinguish a token, no matter which state produced it.
 */
final class LexerBuilderResult
{
    /**
     * A map of token ID and its definition of the initial (non-namespaced)
     * lexer state.
     *
     * @var non-empty-array<int, TokenDefinition>
     */
    public private(set) array $tokens;

    /**
     * A map of state name and its token definitions, indexed by token ID.
     *
     * @var array<non-empty-string, non-empty-array<int, TokenDefinition>>
     */
    public private(set) array $states = [];

    /**
     * A map of token name and its ID, gathered from all states.
     *
     * @var array<non-empty-string, int>
     */
    public private(set) array $constants = [];

    /**
     * A map of token definition and its ID, gathered from all states.
     *
     * @var \SplObjectStorage<TokenDefinition, int>
     */
    private readonly \SplObjectStorage $identifiers;

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
        /**
         * The very same "unknown" definition is shared between all states, so
         * that an unrecognized fragment is always reported using the same
         * token ID, no matter which state the lexer was in.
         */
        $unknown = $this->createUnknownToken();

        $this->identifiers = new \SplObjectStorage();

        $this->tokens = $this->index([...$tokens, $unknown]);

        $result = [];

        foreach ($states as $name => $definitions) {
            $result[$name] = $this->index([...$definitions, $unknown]);
        }

        $this->states = $result;
        $this->constants = $this->createConstants();
    }

    /**
     * Returns the identifier of the given token definition, or {@see null} in
     * case of the token is not recognized by the lexer.
     *
     * @api
     */
    public function findTokenId(TokenDefinition $token): ?int
    {
        return $this->identifiers[$token] ?? null;
    }

    /**
     * Assigns a globally unique identifier to each definition.
     *
     * A definition shared between several states keeps a single identifier.
     *
     * @param non-empty-list<TokenDefinition> $definitions
     * @return non-empty-array<int, TokenDefinition>
     */
    private function index(array $definitions): array
    {
        $identifiers = $this->identifiers;
        $result = [];

        foreach ($definitions as $definition) {
            if (!isset($identifiers[$definition])) {
                $identifiers[$definition] = $identifiers->count();
            }

            $result[$identifiers[$definition]] = $definition;
        }

        return $result;
    }

    /**
     * @return array<non-empty-string, int>
     */
    private function createConstants(): array
    {
        $result = [];

        foreach ([$this->tokens, ...\array_values($this->states)] as $definitions) {
            foreach ($definitions as $id => $definition) {
                if ($definition->name === null) {
                    continue;
                }

                $result[$definition->name] = $id;
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
