<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Analysis;

use Phplrt\Lexer\Builder\Regex\MarkersRegexGenerator;
use Phplrt\Lexer\Builder\Regex\RegexGeneratorInterface;

/**
 * Describes the pattern each lexer state recognizes its tokens with.
 *
 * Every state is a lexer of its own, so it gets a pattern of its own as well.
 */
final readonly class RegexConstructionLexerAnalysisPass implements
    LexerAnalysisPassInterface
{
    public function __construct(
        private RegexGeneratorInterface $generator = new MarkersRegexGenerator(),
    ) {}

    public function process(LexerResultContext $context): void
    {
        $context->pattern = $this->generator->generate($context->tokens, $context->flags);

        $patterns = [];

        foreach ($context->states as $name => $tokens) {
            $patterns[$name] = $this->generator->generate($tokens, $context->flags);
        }

        $context->statePatterns = $patterns;
    }
}
