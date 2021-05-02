<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Exception\Internal\Code;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Position\IntervalInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Position\Interval;
use Phplrt\Position\Position;
use Phplrt\Source\File;
use Phplrt\Source\Reader;
use Phplrt\Source\ReaderInterface;

/**
 * @internal Source is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Exception
 */
final class Code
{
    /**
     * @var ReadableInterface
     */
    private ReadableInterface $source;

    /**
     * @var IntervalInterface
     */
    private IntervalInterface $interval;

    /**
     * @var ReaderInterface
     */
    private ReaderInterface $reader;

    /**
     * @param ReadableInterface $source
     * @param IntervalInterface $interval
     */
    public function __construct(ReadableInterface $source, IntervalInterface $interval)
    {
        $this->source = $source;
        $this->interval = $interval;
        $this->reader = new Reader($source);
    }

    /**
     * @param ReadableInterface $source
     * @param TokenInterface $token
     * @param bool $utf
     * @return static
     */
    public static function fromToken(ReadableInterface $source, TokenInterface $token, bool $utf = true): self
    {
        $length = $utf ? \mb_strlen($token->getValue()) : $token->getBytes();
        $interval = Interval::fromOffset($source, $token->getOffset(), $length);

        return new self($source, $interval);
    }

    /**
     * @param \Throwable $e
     * @return static
     */
    public static function fromException(\Throwable $e): self
    {
        $source = \is_file($e->getFile()) ? File::fromPathname($e->getFile()) : File::empty();

        return new self($source, Interval::fromLineAndColumn($source, $e->getLine()));
    }

    /**
     * @param positive-int|0 $size
     * @return positive-int
     *
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MoreSpecificReturnType
     */
    private function from(int $size): int
    {
        $from = $this->interval->getFrom();

        return \max(Position::MIN_LINE, $from->getLine() - $size);
    }

    /**
     * @param positive-int|0 $size
     * @return positive-int
     */
    private function to(int $size): int
    {
        $to = $this->interval->getTo();

        return $to->getLine() + $size;
    }

    /**
     * @param positive-int|0 $size
     * @return iterable<Line>
     */
    public function get(int $size = 0): iterable
    {
        $from = $this->interval->getFrom();
        $to = $this->interval->getTo();

        foreach ($this->reader->lines($this->from($size), $this->to($size)) as $line => $code) {
            $isError = $line >= $from->getLine() && $line <= $to->getLine();

            yield $isError ? new ErrorLine($line, $code) : new Line($line, $code);
        }
    }
}
