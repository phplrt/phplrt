<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests\Stub;

/**
 * An error that belongs to no file at all, which is what an error restored
 * out of a serialized state looks like.
 */
final class FilelessExceptionStub extends \RuntimeException
{
    public function __construct(string $message = 'Fileless error')
    {
        parent::__construct($message);

        $this->file = '';
        $this->line = 0;
    }
}
