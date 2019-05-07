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
    use MutableExceptionTrait;

    /**
     * @param \Throwable $e
     * @param \Throwable|null $previous
     * @return ExternalExceptionInterface|MutableExceptionInterface|$this
     */
    public static function from(\Throwable $e, \Throwable $previous = null): ExternalExceptionInterface
    {
        $previous = $previous ?? $e->getPrevious();

        return (new static($e->getMessage(), $e->getCode(), $previous))->throwsFrom($e);
    }

    /**
     * @param string $message
     * @param mixed ...$args
     * @return ExternalExceptionInterface|$this
     */
    public static function new(string $message, ...$args): ExternalExceptionInterface
    {
        return new static(\vsprintf($message, ...$args));
    }
}
