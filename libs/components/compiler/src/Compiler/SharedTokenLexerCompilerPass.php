<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Compiler;

use Phplrt\Lexer\Builder\Compiler\LexerBuildingContext;
use Phplrt\Lexer\Builder\Compiler\LexerCompilerPassInterface;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\LexerBuilder;

/**
 * Adds the tokens that belong to every state to each of them.
 *
 * A grammar declares such a token once, while a lexer recognizes the tokens of
 * a single state, so the declaration is spread over the states here: the
 * states a grammar describes are only all known once every grammar has been
 * read, which is why this is done while the lexer is being built rather than
 * while the grammar is being read.
 *
 * A state read by a lexer written by hand is left alone: what it recognizes is
 * decided by that lexer rather than by any declaration.
 */
final class SharedTokenLexerCompilerPass implements LexerCompilerPassInterface
{
    /**
     * The definitions belonging to every state, in the order they are
     * declared.
     *
     * @var list<TokenDefinition>
     */
    public private(set) array $tokens = [];

    /**
     * @api
     *
     * @return $this
     */
    public function addToken(TokenDefinition $definition): self
    {
        $this->tokens[] = $definition;

        return $this;
    }

    public function process(LexerBuildingContext $context): void
    {
        /**
         * The states are reached through the lexer they belong to, so the pass
         * only has something to reach while it is processing that lexer: the
         * one it is called by is the outermost by then.
         */
        if ($this->tokens === [] || $context->isEmbedded) {
            return;
        }

        foreach ($this->tokens as $definition) {
            $context->tokens[] = $definition;
        }

        /** @var \SplObjectStorage<LexerBuilder, null> $visited */
        $visited = new \SplObjectStorage();

        $this->share($context->lexers, $visited);
    }

    /**
     * Adds the shared tokens to every state of the given lexers, and to the
     * states of those states.
     *
     * A definition is what a rule of a parser refers to, and the identifier of
     * a token is its position in the lexer recognizing it, so every state is
     * given a copy of its own: a single definition put in two lexers would be
     * two different tokens told apart by nothing.
     *
     * @param array<non-empty-string, mixed> $lexers
     * @param \SplObjectStorage<LexerBuilder, null> $visited
     */
    private function share(array $lexers, \SplObjectStorage $visited): void
    {
        foreach ($lexers as $lexer) {
            // A lexer written by hand recognizes whatever it recognizes
            if (!$lexer instanceof LexerBuilder || $visited->offsetExists($lexer)) {
                continue;
            }

            $visited->offsetSet($lexer);

            foreach ($this->tokens as $definition) {
                $lexer->addToken(clone $definition);
            }

            $this->share($lexer->lexers, $visited);
        }
    }

    /**
     * Returns the pass the given builder spreads its shared tokens by, adding
     * it to the builder in case of it has none.
     *
     * The tokens are declared by any number of grammars while the lexer they
     * are added to is one, so the pass is kept by the builder rather than by
     * the one reading a grammar: a builder is a single compilation, and the
     * tokens of one compilation never reach another.
     *
     * @api
     */
    public static function of(LexerBuilder $builder): self
    {
        foreach ($builder->compilerPasses as $passes) {
            foreach ($passes as $pass) {
                if ($pass instanceof self) {
                    return $pass;
                }
            }
        }

        $pass = new self();

        $builder->addCompilerPass($pass, LexerBuilder::PASS_PRIORITY_NORMALIZE);

        return $pass;
    }
}
