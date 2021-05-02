<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Exception\Renderer;

use Phplrt\Contracts\Position\IntervalInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\Internal\ANSI;
use Phplrt\Exception\Internal\Code\Code;
use Phplrt\Exception\Internal\Code\ErrorLine;
use Phplrt\Position\Interval;
use Phplrt\Source\File;

final class ConsoleRenderer extends Renderer
{
    use ConsoleOptionsTrait;

    /**
     * ConsoleRenderer constructor.
     */
    public function __construct()
    {
        $this->colors = $this->getEnvColorsSupport();
        $this->columns = $this->getEnvNumberOfColumns();
    }

    /**
     * @param string $line
     * @return string
     */
    private function line(string $line): string
    {
        return ($line ? \str_repeat(' ', $this->offset) : '') . $line;
    }

    /**
     * @return string
     */
    private function getTitleTemplate(): string
    {
        if ($this->colors) {
            return ANSI::CLR_WHITE . ANSI::BG_RED . ANSI::SEQ_BOLD . ' %s ' . ANSI::SEQ_RESET;
        }

        return '[ %s ]';
    }

    /**
     * @param \Throwable $e
     * @return string
     */
    private function renderTitle(\Throwable $e): string
    {
        return \sprintf($this->getTitleTemplate(), \get_class($e));
    }

    /**
     * @return string
     */
    private function getMessageTemplate(): string
    {
        if ($this->colors) {
            return ANSI::SEQ_BOLD . '%s' . ANSI::SEQ_RESET;
        }

        return '%s';
    }

    /**
     * @param \Throwable $e
     * @return string
     */
    private function renderMessage(\Throwable $e): string
    {
        return \sprintf($this->getMessageTemplate(), $e->getMessage());
    }

    /**
     * @return string
     */
    private function getFileTemplate(): string
    {
        if ($this->colors) {
            $placeholder = ANSI::CLR_GREEN . '%s' . ANSI::SEQ_RESET;
            return "at $placeholder:$placeholder";
        }

        return 'at %s:%s';
    }

    /**
     * @param ReadableInterface $source
     * @param IntervalInterface $interval
     * @return string
     */
    private function renderFile(ReadableInterface $source, IntervalInterface $interval): string
    {
        $from = $interval->getFrom();

        return \vsprintf($this->getFileTemplate(), [
            $source instanceof FileInterface ? $source->getPathname() : '<unknown>',
            $from->getLine(),
        ]);
    }

    /**
     * @param \Throwable $e
     * @return string
     */
    public function render(\Throwable $e): string
    {
        $source = \is_file($e->getFile())
            ? File::fromPathname($e->getFile())
            : File::empty()
        ;

        return $this->renderIn($e, $source, Interval::fromLineAndColumn($source, $e->getLine()));
    }

    /**
     * @param \Throwable $e
     * @param ReadableInterface $source
     * @param IntervalInterface $position
     * @return string
     */
    public function renderIn(\Throwable $e, ReadableInterface $source, IntervalInterface $position): string
    {
        return \implode(\PHP_EOL, \array_map(fn(string $line): string => $this->line($line), [
            ...$this->renderLines($e, $source, $position)
        ]));
    }

    /**
     * @param \Throwable $e
     * @param ReadableInterface $source
     * @param IntervalInterface $position
     * @return iterable<string>
     */
    private function renderLines(\Throwable $e, ReadableInterface $source, IntervalInterface $position): iterable
    {
        yield from [
            $this->renderTitle($e),
            '',
            $this->renderMessage($e),
            '',
            $this->renderFile($source, $position)
        ];

        yield from $this->renderCode($source, $position);
        yield '';

        yield from $this->renderTrace($e);
        yield '';
    }

    /**
     * @return iterable<string>
     */
    private function renderTrace(\Throwable $e): iterable
    {
        $trace = $e->getTrace();
        $last = \array_pop($trace);
        $size = \count($trace);
        $prefix = \str_repeat(' ', \strlen((string)$size) + 2);

        if ($this->colors) {
            yield ANSI::CLR_YELLOW . ANSI::SEQ_BOLD . '#' . $size . ' ' . ANSI::SEQ_RESET .
                ANSI::SEQ_BOLD . $last['file'] . ':' . $last['line'] . ANSI::SEQ_RESET;
        } else {
            yield '#' . $size . ' ' . $last['file'] . ':' . $last['line'];
        }

        yield $prefix . $last['class'] . $last['type'] . $last['function'] . '()';

        if ($size !== 0) {
            $frames = $prefix . '+' . $size . ' frames';

            yield $this->colors
                ? ANSI::CLR_DARK_GRAY . $frames . ANSI::SEQ_RESET
                : $frames;
        }
    }

    /**
     * @param ReadableInterface $source
     * @param IntervalInterface $position
     * @return iterable<string>
     */
    private function renderCode(ReadableInterface $source, IntervalInterface $position): iterable
    {
        $code = new Code($source, $position);
        $size = $this->getLineSize($position);

        foreach ($code->get($this->size) as $line) {
            if ($line instanceof ErrorLine) {
                yield \vsprintf($this->getCodeErrorLineTemplate($size), [
                    $line->getNumber(),
                    $line->getCode(),
                ]);

                continue;
            }

            yield \vsprintf($this->getCodeLineTemplate($size), [
                $line->getNumber(),
                $line->getCode(),
            ]);
        }
    }

    /**
     * @param IntervalInterface $position
     * @return positive-int
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MoreSpecificReturnType
     */
    private function getLineSize(IntervalInterface $position): int
    {
        $to = $position->getTo();

        return \strlen((string)$to->getLine());
    }

    /**
     * @param positive-int $size
     * @return string
     */
    private function getCodeLineTemplate(int $size): string
    {
        $suffix = $this->utf ? ' ⏐ ' : ' | ';
        $placeholder = '%' . ($size + 2) . 's' . $suffix;

        if ($this->colors) {
            return ANSI::CLR_DARK_GRAY . $placeholder . ANSI::SEQ_RESET . '%s';
        }

        return $placeholder . '%s';
    }

    /**
     * @param positive-int $size
     * @return string
     */
    private function getCodeErrorLineTemplate(int $size): string
    {
        $prefix = $this->utf ? 'ᐅ ' : '> ';
        $suffix = $this->utf ? ' ⏐ ' : ' | ';

        $placeholder = "$prefix%{$size}s$suffix";

        if ($this->colors) {
            return
                ANSI::CLR_RED . $placeholder . ANSI::SEQ_RESET .
                ANSI::CLR_RED . '%s' . ANSI::SEQ_RESET
            ;
        }

        return $placeholder . '%s';
    }
}
