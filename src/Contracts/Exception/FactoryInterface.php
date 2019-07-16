<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Contracts\Exception;

/**
 * Interface FactoryInterface
 */
interface FactoryInterface
{
    /**
     * @param string $message
     * @param mixed ...$args
     * @return ExternalExceptionInterface|$this
     */
    public static function new(string $message, ...$args): ExternalExceptionInterface;

    /**
     * @param \Throwable $exception
     * @param \Throwable|null $previous
     * @return ExternalExceptionInterface|$this
     */
    public static function from(\Throwable $exception, \Throwable $previous = null): ExternalExceptionInterface;
}
