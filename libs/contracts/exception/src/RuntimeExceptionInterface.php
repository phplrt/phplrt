<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

interface RuntimeExceptionInterface extends \Throwable
{
    public function getToken(): TokenInterface;

    public function getSource(): ReadableInterface;
}
