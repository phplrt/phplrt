<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * An exception that occurs when there is no read access to the file,
 * such as "Access Denied".
 */
class NotReadableException extends NotAccessibleException {}
