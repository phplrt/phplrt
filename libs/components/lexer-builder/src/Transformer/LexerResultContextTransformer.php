<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Transformer;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Lexer\Builder\Analysis\LexerResultContext;
use Phplrt\Lexer\Builder\Compiler\LexerBuildingContext;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Exception\LexerCompilerException;
use Phplrt\Lexer\Builder\LexerBuilder;

/**
 * Assembles the lexer out of the token definitions the compiler passes have
 * left behind.
 *
 * Identifiers are assigned here, so this is the point after which the token
 * definitions may no longer be rewritten. The lexers reading the fragments are
 * compiled here as well, each of them on its own.
 */
final readonly class LexerResultContextTransformer
{
    /**
     * @throws LexerCompilerException
     */
    public function transform(LexerBuildingContext $context): LexerResultContext
    {
        $lexers = [];

        foreach ($context->lexers as $name => $lexer) {
            if (!$lexer instanceof LexerBuilder) {
                $lexers[$name] = $lexer;

                continue;
            }

            $context->logger->info('Building the {lexer} lexer the reading is handed over to', [
                'lexer' => $name,
            ]);

            $lexer->setLogger($context->logger);

            $lexers[$name] = $lexer->build();
        }

        return new LexerResultContext(
            tokens: [...$context->tokens, $this->createUnknownToken()],
            flags: \array_values($context->flags),
            lexers: $lexers,
            logger: $context->logger,
        );
    }

    /**
     * Reads the fragment that none of the token definitions recognizes, so
     * that an unreadable source is reported instead of stopping the analysis.
     */
    private function createUnknownToken(): TokenDefinition
    {
        return new RegexTokenDefinition('[^\\s]++')
            ->setChannel(Channel::Unknown);
    }
}
