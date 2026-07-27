<?php

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\Printer\ErrorInfo;
use Phplrt\Exception\Printer\PrinterInterface;
use Phplrt\Exception\Printer\RustStylePrinter;
use Phplrt\Exception\Snippet\Reader\Content\ContentInterface;
use Phplrt\Exception\Snippet\Reader\Content\FileContent;
use Phplrt\Exception\Snippet\Reader\Content\StringContent;
use Phplrt\Exception\Snippet\Reader\SourceLineReader;

/**
 * Prints an error along with the fragment of the source code it occurred in.
 *
 * Everything the source itself tells about the error is taken from it: where
 * the fragment is written down and what the source is called, so the only
 * thing left to the caller is what the error is about.
 */
final readonly class ErrorPrinter
{
    private SourceLineReader $reader;

    public function __construct(
        private PrinterInterface $printer = new RustStylePrinter(),
    ) {
        $this->reader = new SourceLineReader();
    }

    /**
     * Returns the error occurred at the given position of the given source,
     * described by nothing but the source itself.
     *
     * @param int<0, max> $offset the byte offset of the fragment the error
     *        occurred in
     * @param int<0, max> $length the size of that fragment in bytes
     * @throws SourceExceptionInterface in case the source code cannot be
     *         obtained from the source itself
     */
    public function print(ReadableInterface $source, int $offset, int $length = 0): ErrorInfoResult
    {
        return new ErrorInfoResult(
            reader: $this->reader,
            printer: $this->printer,
            content: $this->getContent($source),
            offset: $offset,
            length: $length,
            // Only a source that belongs to a file can be referred to by its
            // name, be the file a real one or not
            info: new ErrorInfo(
                pathname: $source instanceof FileInterface ? $source->pathname : null,
            ),
        );
    }

    /**
     * A source stored in a file that can be read from the disk is read from
     * there, so that only the fragment around the captured one is loaded.
     * Anything else describes the source code it already holds.
     *
     * @throws SourceExceptionInterface
     */
    private function getContent(ReadableInterface $source): ContentInterface
    {
        if ($source instanceof FileInterface && \is_readable($source->pathname)) {
            return new FileContent($source->pathname);
        }

        return new StringContent($source->content);
    }
}
