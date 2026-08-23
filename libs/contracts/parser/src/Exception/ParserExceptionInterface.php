<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Parser\Exception;

/**
 * An error of the syntax analysis, including an internal one.
 *
 * Every exception thrown by a parser MUST implement this interface.
 */
interface ParserExceptionInterface extends \Throwable {}
