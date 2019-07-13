<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Exception\ExternalExceptionInterface;
use Phplrt\Contracts\Exception\MutableExceptionInterface;

/**
 * Trait FactoryTrait
 */
trait FactoryTrait
{
    /**
     * FactoryTrait constructor.
     *
     * @param string $msg
     * @param int $code
     * @param \Throwable|null $prev
     * @return MutableExceptionInterface
     */
    abstract protected static function create(string $msg, int $code = 0, \Throwable $prev = null): MutableExceptionInterface;

    /**
     * @param \Throwable $e
     * @return MutableExceptionInterface
     */
    abstract public function throwsFrom(\Throwable $e): MutableExceptionInterface;

    /**
     * @param \Throwable $e
     * @param \Throwable|null $previous
     * @return ExternalExceptionInterface|MutableExceptionInterface|$this
     */
    public static function from(\Throwable $e, \Throwable $previous = null): ExternalExceptionInterface
    {
        $previous = $previous ?? $e->getPrevious();

        return static::create($e->getMessage(), $e->getCode(), $previous)->throwsFrom($e);
    }

    /**
     * @param string $message
     * @param mixed ...$args
     * @return ExternalExceptionInterface|$this
     */
    public static function new(string $message, ...$args): ExternalExceptionInterface
    {
        return static::create(\vsprintf($message, ...$args));
    }
}
