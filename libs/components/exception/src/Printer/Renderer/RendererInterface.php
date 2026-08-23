<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer\Renderer;

use Phplrt\Exception\Printer\PrintableError;
use Phplrt\Exception\Snippet\SourceLine;

/**
 * Turns an error along with the lines of the source code it occurred in into
 * a string.
 */
interface RendererInterface
{
    /**
     * Returns the printed representation of the given error.
     *
     * @param iterable<mixed, SourceLine> $snippets the lines of the source
     *        code printed along with the error
     */
    public function render(iterable $snippets, PrintableError $error): string;
}
