<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Exception;

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
     * @return ExternalExceptionInterface|$this
     */
    public static function from(\Throwable $exception): ExternalExceptionInterface;
}
