<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Io\Readable;
use Phplrt\Position\PositionInterface;

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
     * @param string $message
     * @param mixed ...$args
     * @return ExternalExceptionInterface|$this
     */
    public static function new(string $message, ...$args): ExternalExceptionInterface
    {
        return new static(\vsprintf($message, ...$args));
    }

    /**
     * @param \Throwable $exception
     * @return ExternalExceptionInterface|$this
     */
    public function from(\Throwable $exception): ExternalExceptionInterface
    {
        $this->code = $exception->getCode();
        $this->message = $exception->getMessage();

        $this->file = $exception->getFile();
        $this->line = $exception->getLine();

        if ($exception instanceof PositionInterface) {
            $this->column = $exception->getColumn();
        }

        return $this;
    }
}
