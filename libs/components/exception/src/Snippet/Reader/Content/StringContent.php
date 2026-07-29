<?php

declare(strict_types=1);

namespace Phplrt\Exception\Snippet\Reader\Content;

/**
 * The source code available as a string.
 */
final readonly class StringContent implements ContentInterface
{
    public function __construct(
        private string $code,
    ) {}

    public function getSize(): int
    {
        return \strlen($this->code);
    }

    public function findBefore(string $needle, int $offset): ?int
    {
        $size = \strlen($this->code);
        $offset = \max(0, \min($offset, $size));

        if ($offset === 0) {
            return null;
        }

        // A negative offset limits the search to the occurrences starting at
        // the corresponding position or before it.
        $result = \strrpos($this->code, $needle, $offset - $size - 1);

        return $result === false ? null : \max(0, $result);
    }

    public function findAfter(string $needle, int $offset): ?int
    {
        $offset = \max(0, $offset);

        if ($offset >= \strlen($this->code)) {
            return null;
        }

        $result = \strpos($this->code, $needle, $offset);

        return $result === false ? null : $result;
    }

    public function countBefore(string $needle, int $offset): int
    {
        $size = \strlen($this->code);
        $offset = \max(0, \min($offset, $size));

        return \substr_count($this->code, $needle, 0, \min($offset + \strlen($needle) - 1, $size));
    }

    public function read(int $offset, int $length): string
    {
        return \substr($this->code, \max(0, $offset), \max(0, $length));
    }
}
