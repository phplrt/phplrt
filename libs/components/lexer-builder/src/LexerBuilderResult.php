<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Builder\Definition\Lexer\EmbeddedLexerInterface;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Exception\LexerCompilerException;
use Phplrt\Lexer\Builder\Transformer\RuntimeLexerTransformer;

/**
 * Represents the result of building a lexer.
 *
 * A token identifier is the position of its definition in this lexer, so it
 * only means something here: the tokens read by another lexer are carried by
 * the token that entered it and never reach this stream.
 */
final readonly class LexerBuilderResult
{
    public function __construct(
        /**
         * A map of token ID and the definition the lexer recognizes it by.
         *
         * @var non-empty-array<int, TokenDefinition>
         */
        public array $tokens,
        /**
         * A map of name and the lexer reading the fragment it stands for.
         *
         * @var array<non-empty-string, self|EmbeddedLexerInterface>
         */
        public array $lexers,
        /**
         * The pattern recognizing the tokens of the lexer.
         *
         * @var non-empty-string
         */
        public string $pattern,
        /**
         * A map of token ID and the channel it is emitted to.
         *
         * @var array<int, non-empty-string>
         */
        public array $channels,
        /**
         * A map of token ID and its name.
         *
         * @var array<int, non-empty-string>
         */
        public array $names,
        /**
         * A map of token ID and what the token does to the reading.
         *
         * A {@see string} value hands the reading over to the named lexer,
         * while a {@see null} one ends the reading of this one.
         *
         * @var array<int, non-empty-string|null>
         */
        public array $transitions,
    ) {}

    /**
     * @throws LexerCompilerException in case of a fragment cannot be read
     */
    public function toLexer(): LexerInterface
    {
        return new RuntimeLexerTransformer()
            ->transform($this);
    }

    /**
     * Returns the identifier of the given token definition, or {@see null} in
     * case of the token is not recognized by the lexer.
     *
     * @api
     */
    public function findTokenId(TokenDefinition $token): ?int
    {
        $id = \array_search($token, $this->tokens, true);

        return $id === false ? null : $id;
    }
}
