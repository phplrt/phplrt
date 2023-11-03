<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * The exception that occurs in case of file access errors, like "Permission Denied".
 */
class NotAccessibleException extends \RuntimeException implements SourceExceptionInterface {}
