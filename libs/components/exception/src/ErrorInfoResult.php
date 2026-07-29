<?php

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Exception\Printer\ErrorInfo;
use Phplrt\Exception\Printer\Level;
use Phplrt\Exception\Printer\PrinterInterface;
use Phplrt\Exception\Snippet\Exception\SourceNotReadableException;
use Phplrt\Exception\Snippet\Reader\Content\ContentInterface;
use Phplrt\Exception\Snippet\Reader\SourceLineReader;

/**
 * The error along with the fragment of the source code it occurred in, ready
 * to be printed.
 *
 * Everything the error is described by is added afterward, one thing at a
 * time, and the source code is read only at the moment the whole thing is
 * turned into a string.
 *
 * @internal the object is created by the {@see ErrorPrinter}
 */
final readonly class ErrorInfoResult implements \Stringable
{
    /**
     * The default number of lines to read before and after the captured ones.
     *
     * @var int<0, max>
     */
    public const int DEFAULT_LINES_AROUND = 2;

    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     * @param int<0, max> $linesAround
     */
    public function __construct(
        private SourceLineReader $reader,
        private PrinterInterface $printer,
        private ContentInterface $content,
        private int $offset,
        private int $length = 0,
        private ErrorInfo $info = new ErrorInfo(),
        private int $linesAround = self::DEFAULT_LINES_AROUND,
    ) {}

    /**
     * Describes the error by everything the given information tells about it
     * at once.
     */
    public function withErrorInfo(ErrorInfo $info): self
    {
        return $this->with(info: $info);
    }

    /**
     * The message describing the error.
     */
    public function withMessage(?string $message): self
    {
        $info = $this->info;

        return $this->with(info: new ErrorInfo(
            message: $message,
            pathname: $info->pathname,
            class: $info->class,
            level: $info->level,
        ));
    }

    /**
     * The name of the file containing the source code.
     */
    public function withPathname(?string $pathname): self
    {
        $info = $this->info;

        return $this->with(info: new ErrorInfo(
            message: $info->message,
            pathname: $pathname,
            class: $info->class,
            level: $info->level,
        ));
    }

    /**
     * The name identifying the error, like the class of an exception.
     */
    public function withClass(?string $class): self
    {
        $info = $this->info;

        return $this->with(info: new ErrorInfo(
            message: $info->message,
            pathname: $info->pathname,
            class: $class,
            level: $info->level,
        ));
    }

    /**
     * The severity of the error.
     */
    public function withLevel(Level $level): self
    {
        $info = $this->info;

        return $this->with(info: new ErrorInfo(
            message: $info->message,
            pathname: $info->pathname,
            class: $info->class,
            level: $level,
        ));
    }

    /**
     * The number of lines printed before and after the fragment.
     */
    public function withLinesAround(int $lines): self
    {
        return $this->with(linesAround: \max(0, $lines));
    }

    /**
     * The size of the fragment the error occurred in, in bytes.
     *
     * A fragment of a negative size is the same as no fragment at all, so the
     * error points at the position it starts at.
     */
    public function withLength(int $length): self
    {
        return $this->with(length: \max(0, $length));
    }

    /**
     * @throws SourceNotReadableException in case the source code cannot be read
     */
    public function __toString(): string
    {
        return $this->printer->print(
            $this->reader->read($this->content, $this->offset, $this->length, $this->linesAround),
            $this->info,
        );
    }

    /**
     * @param int<0, max>|null $length
     * @param int<0, max>|null $linesAround
     */
    private function with(?int $length = null, ?ErrorInfo $info = null, ?int $linesAround = null): self
    {
        return new self(
            reader: $this->reader,
            printer: $this->printer,
            content: $this->content,
            offset: $this->offset,
            length: $length ?? $this->length,
            info: $info ?? $this->info,
            linesAround: $linesAround ?? $this->linesAround,
        );
    }
}
