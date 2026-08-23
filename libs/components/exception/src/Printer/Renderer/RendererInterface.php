<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer\Renderer;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Exception\Analysis\FailureResult;

/**
 * Turns everything that is known about an error into a string.
 */
interface RendererInterface
{
    /**
     * Returns the printed representation of the given error.
     *
     * @param \Throwable $e the error the information has been built out of
     * @throws SourceExceptionInterface in case the data of the source the
     *         error occurred in cannot be read
     */
    public function render(FailureResult $error, \Throwable $e): string;
}
