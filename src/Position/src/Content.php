<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Position;

use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Class Content
 */
class Content implements ContentInterface
{
    /**
     * @var array|null
     */
    private ?array $lines = null;

    /**
     * @var ReadableInterface
     */
    private ReadableInterface $source;

    /**
     * Content constructor.
     *
     * @param ReadableInterface $source
     */
    public function __construct(ReadableInterface $source)
    {
        $this->source = $source;
    }

    /**
     * @return \Closure
     */
    private function filter(): \Closure
    {
        return fn (string $line) => \trim($line, "\r\0");
    }

    /**
     * @return void
     */
    private function boot(): void
    {
        if ($this->lines === null) {
            $this->lines = \array_map($this->filter(), \explode("\n", $this->source->getContents()));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function line(int $line): string
    {
        $this->boot();

        return $this->lines[$line - 1] ?? '';
    }

    /**
     * {@inheritDoc}
     */
    public function lines(int $line, int $before = 0, int $after = 0): iterable
    {
        $this->boot();

        for ($from = \max(1, $line - $before), $to = $line + $after + 1; $from < $to; ++$from) {
            if (! isset($this->lines[$from])) {
                break;
            }

            yield $this->line($from);
        }
    }
}
