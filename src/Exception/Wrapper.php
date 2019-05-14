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
 * Class Wrapper
 */
class Wrapper
{
    /**
     * @var string
     */
    private $exception;

    /**
     * Wrapper constructor.
     *
     * @param string $defaultException
     */
    public function __construct(string $defaultException = \ErrorException::class)
    {
        $this->exception = $defaultException;
    }

    /**
     * @param \Closure $expression
     * @param \Closure|null $onError
     * @return mixed
     */
    public static function exec(\Closure $expression, \Closure $onError = null)
    {
        return (new static())->wrap($expression, $onError);
    }

    /**
     * @param string $exception
     * @return Wrapper|$this
     */
    public function canThrow(string $exception): self
    {
        $this->exception = $exception;

        return $this;
    }

    /**
     * @param \Closure $expression
     * @param \Closure|null $onError
     * @return mixed
     * @throws \ErrorException
     * @throws \Throwable
     */
    public function wrap(\Closure $expression, \Closure $onError = null)
    {
        $result = $this->execute($expression);

        if ($result instanceof \Generator) {
            while ($result->valid()) {
                $value = $this->wrap(static function () use ($result) {
                    return $result->current();
                });

                $result->send($value);
            }

            return $this->wrap(static function () use ($result) {
                return $result->getReturn();
            });
        }

        $this->throwOnError($onError);

        return $result;
    }

    /**
     * @param \Closure $expression
     * @return mixed
     */
    private function execute(\Closure $expression)
    {
        \error_clear_last();

        $level = \error_reporting(0);
        $result = $expression();
        \error_reporting($level);

        return $result;
    }

    /**
     * @param \Closure|null $onError
     * @return void
     * @throws \Throwable
     */
    private function throwOnError(?\Closure $onError): void
    {
        if ($error = \error_get_last()) {
            ['type' => $code, 'message' => $message] = $error;

            if ($onError) {
                $onError($this->exception($message, $code));
            } else {
                throw $this->exception($message, $code);
            }
        }
    }

    /**
     * @param string $message
     * @param int $code
     * @return \Throwable
     */
    private function exception(string $message, int $code): \Throwable
    {
        $exception = $this->exception;

        return new $exception($message, $code);
    }
}
