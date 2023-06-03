<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

interface RuntimeExceptionInterface extends \Throwable
{
    /**
     * @return TokenInterface
     */
    public function getToken(): TokenInterface;

    /**
     * @return ReadableInterface
     */
    public function getSource(): ReadableInterface;
}
