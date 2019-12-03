<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Parser\Exception;

/**
 * Throws when the error of the parsing of the source code happens.
 *
 * @deprecated since version 2.1 and will be removed in 3.0. Use ParserRuntimeExceptionInterface instead
 */
interface RuntimeExceptionInterface extends ParserRuntimeExceptionInterface
{
}
