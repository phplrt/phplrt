<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Visitor\Exception;

class VisitorException extends \LogicException
{
    /**
     * @var int
     */
    public const ERROR_CODE_ARRAY_ENTERING = 0x01;

    /**
     * @var int
     */
    public const ERROR_CODE_ARRAY_LEAVING = 0x02;

    /**
     * @var int
     */
    public const ERROR_CODE_NODE_ENTERING = 0x03;

    /**
     * @var int
     */
    public const ERROR_CODE_NODE_LEAVING = 0x04;
}
