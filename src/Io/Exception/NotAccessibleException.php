<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Io\Exception;

/**
 * The exception that occurs in case of file access
 * errors, like "Permission Denied".
 */
class NotAccessibleException extends FileException
{
}
