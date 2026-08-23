<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\Analysis\AnalyzedExceptionResult;
use Phplrt\Exception\Analysis\FailureInterval;
use Phplrt\Exception\Printer\Renderer\RendererInterface;
use Phplrt\Exception\SnippetReader;

/**
 * The error along with the fragment of the source code it occurred in, ready
 * to be printed.
 *
 * Everything the error is described by comes from its own analysis and MAY be
 * replaced afterward, one thing at a time, while the source code is read only
 * at the moment the whole thing is turned into a string.
 *
 * @internal the object is created by the {@see ErrorPrinter}
 */
final class PrintableError implements \Stringable
{
    /**
     * The names of the properties that are computed out of the others, so
     * the constructor takes no argument of any of them.
     *
     * @var array<non-empty-string, true>
     */
    private const array COMPUTED = [
        'pathname' => true,
    ];

    /**
     * The name of the file the source code is stored in, or {@see null} in
     * case the source code belongs to no file at all.
     *
     * The file the error has been thrown from names the source as long as the
     * source itself is stored in no file of its own.
     */
    public ?string $pathname {
        get {
            $source = $this->error->source;

            // Only a source that belongs to a file can be referred to by its
            // name, be the file a real one or not
            if ($source instanceof FileInterface) {
                return $this->findRealPathname($source->pathname);
            }

            $pathname = $this->error->exception->getFile();

            return $pathname === '' ? null : $this->findRealPathname($pathname);
        }
    }

    public function __construct(
        private readonly SnippetReader $reader,
        private readonly RendererInterface $renderer,
        /**
         * Everything that is known about the error being printed.
         */
        public readonly AnalyzedExceptionResult $error,
        /**
         * The message describing the error, which is an empty string in case the
         * error is printed without one.
         */
        public private(set) ?string $message = null {
            get => $this->message ?? $this->error->exception->getMessage();
            set => $this->message = $value;
        },
        /**
         * The name identifying the error, which is the class of it in case
         * the error is printed under no name of its own.
         */
        public private(set) ?string $class = null {
            get => $this->class ?? $this->error->exception::class;
            set => $this->class = $value;
        },
        /**
         * The severity of the error, which is the one the error tells about
         * itself in case it is printed under no severity of its own.
         */
        public private(set) ?Level $level = null {
            get => $this->level ?? Level::fromException($this->error->exception);
            set => $this->level = $value;
        },
        /**
         * The number of lines printed before and after the fragment.
         *
         * @var int<0, max>
         */
        public readonly int $linesAround = SnippetReader::DEFAULT_LINES_AROUND,
    ) {}

    /**
     * The error the information is about.
     */
    public function withException(\Throwable $e): self
    {
        return $this->with(error: $this->error->with(exception: $e));
    }

    /**
     * The source the error occurred in.
     */
    public function withSource(ReadableInterface $source): self
    {
        return $this->with(error: $this->error->with(source: $source));
    }

    /**
     * The place inside the source the error occurred at.
     */
    public function withPosition(PositionInterface $position): self
    {
        return $this->with(error: $this->error->with(position: $position));
    }

    /**
     * The fragment of the source the error occurred in, counted in bytes from
     * the beginning of the source.
     *
     * @param int<0, max> $offset the offset the fragment starts at
     * @param int<0, max> $length the size of the fragment
     */
    public function withInterval(int $offset, int $length = 0): self
    {
        return $this->with(error: $this->error->with(
            interval: new FailureInterval(\max(0, $offset), \max(0, $length)),
        ));
    }

    /**
     * The error covers no fragment of the source, so it points at the place
     * it occurred at.
     */
    public function withoutInterval(): self
    {
        return $this->with(error: $this->error->with(interval: null));
    }

    /**
     * The message printed instead of the one the error tells, or {@see null}
     * to print the one it tells itself.
     */
    public function withMessage(?string $message): self
    {
        return $this->with(message: $message);
    }

    /**
     * The name printed instead of the class of the error, or {@see null} to
     * print the class of it.
     */
    public function withClass(?string $class): self
    {
        return $this->with(class: $class);
    }

    /**
     * The severity printed instead of the one the error tells, or {@see null}
     * to print the one it tells itself.
     */
    public function withLevel(?Level $level): self
    {
        return $this->with(level: $level);
    }

    /**
     * The number of lines printed before and after the fragment.
     */
    public function withLinesAround(int $lines): self
    {
        return $this->with(linesAround: \max(0, $lines));
    }

    /**
     * The renderer turning the error into a string.
     */
    public function withRenderer(RendererInterface $renderer): self
    {
        return $this->with(renderer: $renderer);
    }

    /**
     * The reader of the source code lines the error is printed along with.
     */
    public function withReader(SnippetReader $reader): self
    {
        return $this->with(reader: $reader);
    }

    /**
     * Returns the printed representation of the error.
     *
     * @throws SourceExceptionInterface in case the data of the source cannot
     *         be read
     */
    public function render(): string
    {
        $snippet = $this->reader->read($this->error, $this->linesAround);

        return $this->renderer->render($snippet, $this);
    }

    /**
     * @throws SourceExceptionInterface in case the data of the source cannot
     *         be read
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Creates a new instance with one or more overrides.
     *
     * ```
     * $error = $error->with(
     *     message: 'Something went wrong',
     *     level: Level::Warning,
     * );
     * ```
     */
    private function with(mixed ...$parameters): self
    {
        /** @phpstan-ignore argument.type */
        return new self(...[
            ...\array_diff_key(\get_object_vars($this), self::COMPUTED),
            ...$parameters,
        ]);
    }

    /**
     * Returns the physical pathname of the given file, or the given one in
     * case there is no file behind it.
     *
     * @param non-empty-string $pathname
     * @return non-empty-string
     */
    private function findRealPathname(string $pathname): string
    {
        $result = \realpath($pathname);

        return $result === false ? $pathname : $result;
    }
}
