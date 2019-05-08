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
 * An exception that occurs when there is no
 * read access to the file, such as "Access Denied".
 */
class NotReadableException extends NotAccessibleException
{
}
