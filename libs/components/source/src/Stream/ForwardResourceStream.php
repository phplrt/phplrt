<?php

declare(strict_types=1);

namespace Phplrt\Source\Stream;

use Phplrt\Source\Exception\NotReadableException;

/**
 * A cursor that reads a resource stream from the position it has been given
 * at to the very end of it, and never returns back
 *
 * @internal do not work with this implementation directly, use the interface instead
 */
final class ForwardResourceStream extends ResourceStream
{
    /**
     * The byte that has already been read out of the resource in order to
     * find out whether the end has been reached.
     */
    private string $peeked = '';

    /**
     * @var int<0, max>
     */
    public private(set) int $offset = 0;

    public bool $isEof {
        /**
         * @throws NotReadableException When the stream cannot be read
         */
        get {
            if ($this->peeked !== '') {
                return false;
            }

            return ($this->peeked = $this->fetch(1)) === '';
        }
    }

    /**
     * @throws NotReadableException When the stream cannot be read
     */
    public function read(int $bytes): string
    {
        // Invariant against the callers not covered by static analysis.
        if ($bytes < 1) { // @phpstan-ignore smaller.alwaysFalse
            throw new \InvalidArgumentException('Number of bytes to read must be greater than 0');
        }

        $peeked = $this->peeked;
        $this->peeked = '';

        // The byte that has been peeked at is a part of the result, so only
        // the rest of the requested data is read out of the resource.
        if ($peeked === '') {
            $result = $this->fetch($bytes);
        } elseif ($bytes === 1) {
            $result = $peeked;
        } else {
            $result = $peeked . $this->fetch($bytes - 1);
        }

        $this->offset += \strlen($result);

        return $result;
    }
}
