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
 * Class ErrorWrapper
 */
class ErrorWrapper
{
    /**
     * @param \Closure $expression
     * @return mixed
     */
    public static function wrap(\Closure $expression)
    {
        \error_clear_last();

        $level = \error_reporting(0);
        $result = $expression();
        \error_reporting($level);

        if ($result === false) {
            throw static::fromError();
        }

        return $result;
    }

    /**
     * @param \Closure $expressions
     * @return mixed
     */
    public static function wrapMany(\Closure $expressions)
    {
        $iterator = $expressions();

        if (! $iterator instanceof \Generator) {
            throw new \InvalidArgumentException('Expressions list should be an instance of \Generator');
        }

        while ($iterator->valid()) {
            $value = static::wrap(static function () use ($iterator) {
                return $iterator->current();
            });

            $iterator->send($value);
        }

        return static::wrap(static function () use ($iterator) {
            return $iterator->getReturn();
        });
    }

    /**
     * @return \RuntimeException
     */
    private static function fromError(): \RuntimeException
    {
        if ($error = \error_get_last()) {
            ['type' => $code, 'message' => $message] = $error;

            return new \RuntimeException($message, $code);
        }

        return new \RuntimeException();
    }
}
