<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Exception\MutableException\MutableExceptionTrait;

/**
 * Class ExternalException
 */
class ExternalException extends \Exception implements
    FactoryInterface,
    MutableExceptionInterface,
    ExternalExceptionInterface
{
    use FactoryTrait;
    use MutableExceptionTrait;

    /**
     * @param string $msg
     * @param int $code
     * @param \Throwable|null $prev
     * @return MutableExceptionInterface
     */
    protected static function create(string $msg, int $code = 0, \Throwable $prev = null): MutableExceptionInterface
    {
        return new static($msg, $code, $prev);
    }
}
