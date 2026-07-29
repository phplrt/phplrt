<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer;

use Phplrt\Exception\Snippet\SourceLine;

interface PrinterInterface
{
    /**
     * @param iterable<mixed, SourceLine> $snippets
     * @param ErrorInfo|null $info the additional information about the error
     *        printed along with the source code lines
     */
    public function print(iterable $snippets, ?ErrorInfo $info = null): string;
}
