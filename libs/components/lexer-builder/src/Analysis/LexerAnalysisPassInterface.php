<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Analysis;

/**
 * Interface that must be implemented by analysis passes.
 */
interface LexerAnalysisPassInterface
{
    /**
     * You can describe the assembled lexer here before it is dumped.
     *
     * @throws \Throwable in case of any error
     */
    public function process(LexerResultContext $context): void;
}
