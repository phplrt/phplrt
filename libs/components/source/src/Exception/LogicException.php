<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

/**
 * An error in the code working with a source rather than a failure of the
 * source itself.
 */
abstract class LogicException extends \LogicException implements
    SourceExceptionInterface {}
