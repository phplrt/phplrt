<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\StackTrace;

use Phplrt\StackTrace\Record\RecordInterface;

/**
 * Class Trace
 */
class Trace
{
    /**
     * @var \SplStack|RecordInterface[]
     */
    private $trace;

    /**
     * Trace constructor.
     */
    public function __construct()
    {
        $this->trace = new \SplStack();
    }

    /**
     * @param RecordInterface $record
     * @return void
     */
    public function push(RecordInterface $record): void
    {
        $this->trace->push($record);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->trace->count() === 0;
    }

    /**
     * @return RecordInterface|null
     */
    public function pop(): ?RecordInterface
    {
        return $this->trace->isEmpty() ? null : $this->trace->pop();
    }

    /**
     * @return RecordInterface|null
     */
    public function last(): ?RecordInterface
    {
        return $this->trace->isEmpty() ? null : $this->trace->top();
    }

    /**
     * @param \Throwable $e
     * @param bool $pop
     * @return \Throwable
     * @throws \ReflectionException
     */
    public function patch(\Throwable $e, bool $pop = false): \Throwable
    {
        if ($last = ($pop ? $this->pop() : $this->last())) {
            $this->insert($e, 'line', $last->getLine());
            $this->insert($e, 'file', $last->getFile());
        }

        return $e;
    }

    /**
     * @param \Throwable $ctx
     * @param string $property
     * @param mixed $value
     * @return void
     * @throws \ReflectionException
     */
    private function insert(\Throwable $ctx, string $property, $value): void
    {
        $reflection = new \ReflectionProperty($ctx, $property);

        $reflection->setAccessible(true);
        $reflection->setValue($ctx, $value);
    }

    /**
     * @param \Closure $each
     * @return string
     */
    public function render(\Closure $each): string
    {
        $result = [];

        foreach ($this->trace as $item) {
            $result[] = $item->render(\count($result), $each);
        }

        return \implode("\n", $result);
    }
}
