<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source\Exception;

/**
 * An error that occurs while processing the data of a source.
 *
 * Every exception thrown by a source MUST implement this interface.
 */
interface SourceExceptionInterface extends \Throwable {}
