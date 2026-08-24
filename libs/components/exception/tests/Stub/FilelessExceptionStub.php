<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests\Stub;

final class FilelessExceptionStub extends \RuntimeException
{
    public function __construct(string $message = 'Fileless error')
    {
        parent::__construct($message);

        $this->file = '';
        $this->line = 0;
    }
}
