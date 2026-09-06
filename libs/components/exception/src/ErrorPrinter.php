<?php

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Exception\Printer\PrintableError;
use Phplrt\Exception\Printer\Renderer\RendererInterface;
use Phplrt\Exception\Printer\Renderer\RustStyleRenderer;

/**
 * Prints an error along with the fragment of the source code it occurred in.
 *
 * Everything the error tells about itself is taken from it: the source, the
 * place inside that source, the message and the name of the error, so there
 * is nothing left for the caller to describe.
 *
 * @readonly
 */
final class ErrorPrinter
{
    private readonly RendererInterface $renderer;

    /**
     * @param RendererInterface|null $renderer the renderer turning the errors
     *        into the strings, or {@see null} in case the decision belongs to
     *        the output itself
     */
    public function __construct(
        ?RendererInterface $renderer = null,
        private readonly Analyzer $analyzer = new Analyzer(),
    ) {
        $this->renderer = $renderer ?? RustStyleRenderer::createDefault();
    }

    /**
     * Returns the given error described by everything it tells about itself.
     *
     * @throws SourceExceptionInterface in case the data of the source the
     *         error occurred in cannot be read
     */
    public function print(\Throwable $e): PrintableError
    {
        return new PrintableError(
            $this->renderer,
            $this->analyzer->analyze($e),
            $e,
        );
    }
}
