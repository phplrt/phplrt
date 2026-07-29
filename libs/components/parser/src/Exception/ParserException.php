<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Parser\Exception\ParserExceptionInterface;

/**
 * An error that occurs before the analysis starts, so it is about the parser
 * itself rather than about the source code it reads.
 */
class ParserException extends \RuntimeException implements
    ParserExceptionInterface {}
