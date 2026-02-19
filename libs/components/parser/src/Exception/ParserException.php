<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Parser\ParserExceptionInterface;

class ParserException extends \RuntimeException implements
    ParserExceptionInterface {}
