<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * @deprecated since phplrt 3.6 and will be removed in 4.0, please use specific
 *             exception interfaces instead.
 */
interface RuntimeExceptionInterface extends \Throwable
{
    public function getToken(): TokenInterface;

    public function getSource(): ReadableInterface;
}
