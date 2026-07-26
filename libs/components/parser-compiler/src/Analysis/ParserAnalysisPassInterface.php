<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Analysis;

/**
 * Interface that must be implemented by analysis passes.
 */
interface ParserAnalysisPassInterface
{
    /**
     * You can describe the assembled grammar here before it is dumped.
     *
     * @throws \Throwable in case of any error
     */
    public function process(ParserAnalysis $analysis): void;
}
