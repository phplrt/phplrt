<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer;

/**
 * The severity of the error.
 */
enum Level: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Debug = 'debug';
}
