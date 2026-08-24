<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

/**
 * A failure of a source: what it refers to cannot be reached, or the data of
 * it cannot be taken out.
 */
abstract class RuntimeException extends \RuntimeException implements
    SourceExceptionInterface {}
